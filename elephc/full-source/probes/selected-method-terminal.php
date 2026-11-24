<?php
class TerminalSelectionFactory {
    public function newInstanceArgs(array $args): string {return 'ok';}
}
class TerminalSelectionMagic {
    public function __call(string $name,array $args): string {return 'magic';}
}
function terminal_selection_call(mixed $factory,array $args): mixed {
    return $factory->newInstanceArgs($args);
}
$reflection = new ReflectionClass(TerminalSelectionFactory::class);
echo terminal_selection_call(new TerminalSelectionFactory(), array()), '|';
echo terminal_selection_call(new TerminalSelectionMagic(), array());
