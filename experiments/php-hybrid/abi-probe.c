#include "libwp_extended.h"
#include <inttypes.h>
#include <stdio.h>
#include <string.h>
uint64_t observed_r14;
extern int32_t probe_autop_r14(const char *, size_t, int64_t, char **, size_t *);
extern int32_t probe_serialize_r14(const char *, size_t, char **, size_t *);
int main(int argc, char **argv) {
    if ((argc != 2 && argc != 3) || elephc_init() != ELEPHC_STATUS_OK) return 2;
    char *output = NULL;
    size_t length = 0;
    int32_t status = argc == 3 ? probe_serialize_r14(argv[2], strlen(argv[2]), &output, &length) : probe_autop_r14(argv[1], strlen(argv[1]), 1, &output, &length);
    printf("{\"status\":%d,\"expected_r14\":\"123456789abcdef0\",\"observed_r14\":\"%016" PRIx64 "\",\"output_bytes\":%zu}\n",status,observed_r14,length);
    elephc_free(output);
    elephc_shutdown();
    return status != ELEPHC_STATUS_OK;
}
