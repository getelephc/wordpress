"""Inspect ELF64 objects and copy them with section placement names only."""

from contextlib import AbstractContextManager
import hashlib
import mmap
from pathlib import Path
import shutil
import struct


class ElfObject(AbstractContextManager):
    """Read x86_64 relocatable ELF, including extended section indices."""

    def __init__(self, path):
        self.path = Path(path)
        self.file = self.path.open("rb")
        try:
            self.data = mmap.mmap(self.file.fileno(), 0, access=mmap.ACCESS_READ)
            header = struct.unpack_from("<16sHHIQQQIHHHHHH", self.data)
            if header[0][:6] != b"\x7fELF\x02\x01" or header[1:3] != (1, 62):
                raise ValueError("WordPress section placement requires an ELF64 x86_64 object")
            self.header_offset, self.header_size = header[6], header[11]
            count = header[12] or self.section_header(0)[5]
            self.headers = [self.section_header(i) for i in range(count)]
            self.name_index = header[13] if header[13] != 65535 else self.headers[0][6]
            names = self.section_bytes(self.name_index)
            self.names = [self.string(names, section[0]) for section in self.headers]
        except BaseException:
            if hasattr(self, "data"):
                self.data.close()
            self.file.close()
            raise

    def __exit__(self, *args):
        self.data.close()
        self.file.close()

    def section_header(self, index):
        return struct.unpack_from("<IIQQQQIIQQ", self.data,
                                  self.header_offset + index * self.header_size)

    def section_bytes(self, index):
        section = self.headers[index]
        return self.data[section[4]:section[4] + section[5]]

    @staticmethod
    def string(data, offset):
        return data[offset:data.index(0, offset)].decode("utf-8")

    def symbols(self):
        """Yield symbol metadata with SHN_XINDEX resolved before classifying code."""
        for table_index, table in enumerate(self.headers):
            if table[1] != 2:
                continue
            strings = self.section_bytes(table[6])
            extended = next((item for item in self.headers
                             if item[1] == 18 and item[6] == table_index), None)
            for index, offset in enumerate(range(table[4], table[4] + table[5], table[9])):
                name, info, other, section, value, size = struct.unpack_from("<IBBHQQ", self.data, offset)
                common = section == 65522
                if section == 65535 and extended:
                    section = struct.unpack_from("<I", self.data, extended[4] + index * 4)[0]
                yield {"name": self.string(strings, name), "section": section,
                       "common": common, "value": value, "size": size,
                       "binding": info >> 4, "visibility": other & 3}

    def executable_sections(self):
        return {name: section[5] for name, section in zip(self.names, self.headers)
                if section[2] & 4 and section[5]}

    def allocated_digest(self):
        """Hash original allocated contents, excluding section names and file padding."""
        digest = hashlib.sha256()
        for section in self.headers:
            if not section[2] & 2 or section[1] == 8:
                continue
            digest.update(struct.pack("<QQ", section[2], section[5]))
            for offset in range(section[4], section[4] + section[5], 16 * 1024 * 1024):
                digest.update(self.data[offset:min(offset + 16 * 1024 * 1024,
                                                   section[4] + section[5])])
        return digest.hexdigest()

    def placement_copy(self, output, first, central):
        """Change only section names on a copy; keep code, symbols and relocations intact."""
        output = Path(output)
        if output.resolve() == self.path.resolve():
            raise ValueError("The original compiler object must remain unchanged")
        shutil.copyfile(self.path, output)
        names = bytearray(self.section_bytes(self.name_index))
        changes = []
        with output.open("r+b") as stream:
            copied = mmap.mmap(stream.fileno(), 0)
            for index, name in enumerate(self.names):
                prefix = ".wp.center." if name in central else ".wp.first." if name in first else None
                if prefix is None:
                    continue
                offset = len(names)
                names.extend((prefix + name).encode() + b"\0")
                struct.pack_into("<I", copied, self.header_offset + index * self.header_size, offset)
                changes.append({"section_index": index, "original": name, "placed": prefix + name})
            end = len(copied)
            header = self.header_offset + self.name_index * self.header_size
            struct.pack_into("<Q", copied, header + 24, end)
            struct.pack_into("<Q", copied, header + 32, len(names))
            copied.flush()
            copied.close()
            stream.seek(end)
            stream.write(names)
        return changes
