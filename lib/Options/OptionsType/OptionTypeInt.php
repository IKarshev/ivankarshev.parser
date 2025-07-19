<?
namespace Ivankarshev\Parser\Options\OptionsType;

use Ivankarshev\Parser\Options\{AbstractOptionType, OptionTypeInterface};
use Exception;

/**
 * @author Karshev Ivan — https://github.com/IKarshev
 * @category ModuleOptions
 */
final Class OptionTypeInt extends AbstractOptionType implements OptionTypeInterface
{
    protected $Code;
    protected $Value;
    protected $IsRequired;
    protected $IsMultiple;

    protected const TYPE = 'int';

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
     * Производим валидацию и устанавливаем значение объекту
     * 
     * @param mixed[array|int]
     * @return void
     */
    public function setValue(mixed $value): void
    {
        try {
            if( is_array($value) && empty($value) && $this->IsRequired) throw new Exception("Пустое значение свойства");
            if( is_array($value) ){
                if( !$this->IsMultiple ) throw new Exception("Передано множественное значение не множественному свойству", 2);
                foreach ($value as $subValue) {
                    if( !is_int($subValue) ) throw new Exception("Свойство должно содержать int или перечисление int");
                }
            }else if( !is_int($value) ){
                throw new Exception("Свойство должно содержать int или перечисление int");
            }else{
                throw new Exception("Не корректное значение для свойства");
            }
            $this->Value = $value;
        } catch (\Throwable $th) {
            throw $th;   
        }
    }
}
?>