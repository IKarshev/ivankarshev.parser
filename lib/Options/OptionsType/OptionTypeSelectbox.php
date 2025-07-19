<?
namespace Ivankarshev\Parser\Options\OptionsType;

use Ivankarshev\Parser\Options\{AbstractOptionType, OptionTypeInterface};
use Exception;

/**
 * @author Karshev Ivan — https://github.com/IKarshev
 * @category ModuleOptions
 */
final Class OptionTypeSelectbox extends AbstractOptionType implements OptionTypeInterface
{
    protected $Code;
    protected $Value;
    protected $IsRequired;
    protected $IsMultiple;
    
    protected const TYPE = 'selectbox';

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

        $this->setValue($value);
        parent::__construct($this->Code, $this->Value, $this->IsRequired, $this->IsMultiple);
    }

    /**
     * Производим валидацию и устанавливаем значение объекту
     * 
     * @param mixed[array|string]
     * @return void
     */
    public function setValue(mixed $value): void
    {
        try {
            if( is_array($value) && empty($value) && $this->IsRequired) throw new Exception("Пустое значение свойства");
            if( is_array($value) && count($value) > 1 ){
                if( !$this->IsMultiple ) throw new Exception("Передано множественное значение не множественному свойству", 2);
                foreach ($value as $subValue) {
                    if( !is_string($subValue) ) throw new Exception("Свойство должно содержать string или перечисление string");
                }
            }elseif( is_string($value) && $this->isMultiple ){
                throw new Exception("Передано не множественное значение множественному свойству");
            }elseif( !is_string($value) && (!is_array($value) && $this->isMultiple) ){
                throw new Exception("Свойство должно содержать string или перечисление string");
            }
            $this->Value = $value;
        } catch (\Throwable $th) {
            throw $th;   
        }
    }
}
?>