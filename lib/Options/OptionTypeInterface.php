<?
namespace Ivankarshev\Parser\Options;

/**
 * @author Karshev Ivan — https://github.com/IKarshev
 * @category ModuleOptions
 */
interface OptionTypeInterface
{
    public function getValue(); // Получаем данные
    public function setValue(mixed $value): void; // Записываем данные

    public function isMultiple(): bool; // Является ли свойство множественным
    public function isRequired(): bool; // Является ли свойство обязательным
}
?>