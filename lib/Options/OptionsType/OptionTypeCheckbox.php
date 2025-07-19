<?
namespace Ivankarshev\Parser\Options\OptionsType;

use Ivankarshev\Parser\Options\{AbstractOptionType, OptionTypeInterface};
use Exception;

/**
 * @author Karshev Ivan — https://github.com/IKarshev
 * @category ModuleOptions
 */
final Class OptionTypeCheckbox extends AbstractOptionType implements OptionTypeInterface
{
    protected $Code;
    protected $Value;
    protected $IsRequired;
    protected $IsMultiple;

    public const AVAILABLE_STRING_VALUE = ['', 'N', 'Y'];

    protected const TYPE = 'checkbox';

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

        $this->setValue($value);
        parent::__construct($this->Code, $this->Value, $this->IsRequired, $this->IsMultiple);
    }

    /**
     * @return mixed - значения свойства
     */
    public function getValue(): bool
    {
        return $this->Value === 'Y';
    }

    /**
     * Производим валидацию и устанавливаем значение объекту
     * 
     * @param mixed $value - [array|bool|string['', 'Y', '']]
     * @return void
     */
    public function setValue(mixed $value): void
    {
        try {
            if( is_array($value) && empty($value) && $this->IsRequired) throw new Exception("Пустое значение свойства");

            if( is_array($value) ){
                if( !$this->IsMultiple ) throw new Exception("Передано множественное значение не множественному свойству", 2);
                foreach ($value as $subValue) {
                    if( is_bool($subValue) || (is_string($subValue) && !in_array($subValue, self::AVAILABLE_STRING_VALUE)) ){
                        throw new Exception("Свойство должно содержать bool или множество bool или одно из значений: ".implode(',', self::AVAILABLE_STRING_VALUE));
                    }
                }
            }elseif( is_string($value) && !in_array($value, self::AVAILABLE_STRING_VALUE) ){
                throw new Exception("Свойство должно содержать bool или множество bool или одно из значений: ".implode(',', self::AVAILABLE_STRING_VALUE));
            };

            if(is_string($value) && in_array(trim($value), ['', 'N'])){
                $this->Value = 'N';
            }elseif(is_string($value) && trim($value) == 'Y'){
                $this->Value = 'Y';
            }else{
                $this->Value = $value ? 'Y' : 'N';
            }
        } catch (\Throwable $th) {
            throw $th;   
        }
    }
}
?>