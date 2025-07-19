<?
namespace Ivankarshev\Parser\Options;

/**
 * @author Karshev Ivan — https://github.com/IKarshev
 * @category ModuleOptions
 */
abstract Class AbstractOptionType
{
    protected $Code;
    protected $Value;
    protected $IsRequired;
    protected $IsMultiple;
    protected $Name;
    protected $Variants;

    /**
     * @param string $code - код свойства
     * @param $value - значение
     * @param bool $isRequired - множественное ли свойства
     * @param bool $isMultiple - обязательное ли свойство
     */
    public function __construct(string $code, $value, bool $isRequired, bool $isMultiple)
    {
        $this->Code = $code;
        $this->Value = $value;
        $this->IsRequired = $isRequired;
        $this->IsMultiple = $isMultiple;
        $this->Variants = [];
        $this->Name = '';
    }

    public function setName(string $name): void
    {
        $this->Name = $name;
    }

    public function getName(): string
    {
        return $this->Name;
    }

    /**
     * @return bool - является ли свойство множественным
     */
    public function isMultiple(): bool
    {
        return $this->IsMultiple;
    }

    /**
     * @return bool - является ли свойство обязательным
     */
    public function isRequired(): bool
    {
        return $this->IsRequired;
    }

    /**
     * @return string - код свойства
     */
    public function getCode(): string
    {
        return $this->Code;
    }

    /**
     * @return mixed - значения свойства
     */
    public function getValue(): mixed
    {
        return $this->Value;
    }

    /**
     * @return ?string - Тип
     */
    public function getType(): ?string
    {
        return (defined('static::TYPE')) ? static::TYPE : null;
    }

    /**
     * @return ?array - Варианты выбора (не для всех типов свойств)
     */
    public function getVariants(): ?array
    {
        return $this->Variants ?? null;
    }

    /**
     * @param array $values - массив вариантов значений
     */
    public function setVariants(array $values): void
    {
        $this->Variants = $values;
    }
}
?>