<?php

/**
 * Repositorio {@link https://github.com/yordanny90/DataGetter}
 *
 * > Requiere PHP 7.1+, 8.0+
 *
 * - Los datos son de solo lectura, la escritura por medio de esta clase está deshabilitada y genera un {@see E_USER_NOTICE}
 * - Tiene funciones para obtener el valor solo si el tipo de dato es el esperado, de lo contrario devuelve `null`
 * - Tiene funciones para encontrar el primer valor que cumpla con el criterio de tipo de dato
 * - Se comporta como `string` si es necesario, el valor es generado por {@see DataGetter::string_like()}, pero en caso de `null` se convierte en un string vacío ("")
 * - Se comporta como un `iterador` si es necesario, pero en caso de `null` devuelve un iterador vacío
 * - Devuelve el valor original cuando se convierte en JSON
 *
 * Brinda varias formas de obtener valores y evitar errores:
 * ```php
 * $data=new DataGetter($_POST);
 * $r1=$data->A->B->C->numeric();
 * $r2=$data['A']['B']['C']->numeric();
 * $r3=$data->path('A/B/C')->numeric();
 * $r4=$data('A','B','C')->numeric();
 * // Los valores de $r1, $r2, $r3 y $r4 son iguales
 * ```
 *
 * Puede comprobar la existencia de un dato con la funcion {@see isset}:
 * ```php
 * isset($data->A->B->C);
 * isset($data['A']['B']['C']);
 * ```
 */
class DataGetter implements ArrayAccess, IteratorAggregate, JsonSerializable{
    private $val;

    public function __construct($value){
        $this->_val($value);
    }
    
    protected function _val($value){
        if(is_a($value, self::class)) $value=$value->val();
        $this->val=$value;
    }

    /**
     * @return mixed|null
     */
    public function val(){
        return $this->val;
    }

    /**
     * @return int|null
     * @see is_int()
     */
    public function int(): ?int{
        if(is_int($this->val)) return $this->val;
        return null;
    }

    /**
     * @return float|INF|NAN|null
     * @see is_float()
     */
    public function float_inf(): ?float{
        if(is_float($this->val)) return $this->val;
        return null;
    }

    /**
     * @return float|null
     * @see is_float()
     * @see is_finite()
     */
    public function float(): ?float{
        if(is_float($this->val)) return is_finite($this->val)?$this->val:null;
        return null;
    }

    /**
     * @return float|int|string|INF|NAN|null
     * @see is_numeric()
     * @see DataGetter::string_like()
     */
    public function numeric_inf(){
        $val=static::obj2string($val) ?? $this->val;
        if(is_numeric($val)) return $val;
        return null;
    }

    /**
     * @return float|int|string|null
     * @see is_float()
     * @see is_finite()
     * @see DataGetter::string_like()
     * @see is_numeric()
     */
    public function numeric(){
        if(is_float($this->val)) return is_finite($this->val)?$this->val:null;
        $val=static::obj2string($this->val) ?? $this->val;
        if(is_numeric($val)) return $val;
        return null;
    }

    public function bool(): ?bool{
        if(is_bool($this->val)) return $this->val;
        return null;
    }

    /**
     * @return bool|float|int|string|null
     */
    public function scalar(){
        if(is_scalar($this->val)) return $this->val;
        return null;
    }

    public function string(): ?string{
        if(is_string($this->val)) return $this->val;
        return null;
    }

    private static function obj2string($val): ?string{
        if(is_object($val)){
            try{
                return strval($val);
            }catch(Error $e){
            }
        }
        return null;
    }

    /**
     * Si el valor es un escalar o un objeto con el método mágico `__toString`, se convierte en string con {@see strval()}
     * @return string|null
     * @link https://www.php.net/manual/es/language.oop5.magic.php
     * @see is_scalar()
     */
    public function string_like(): ?string{
        if(is_scalar($this->val)) return strval($this->val);
        return static::obj2string($this->val);
    }

    /**
     * @return resource|null
     */
    public function resource(){
        if(is_resource($this->val)) return $this->val;
        return null;
    }

    /**
     * @return callable|null
     */
    public function callable(): ?callable{
        if(is_callable($this->val)) return $this->val;
        return null;
    }

    /**
     * @return iterable|null Devuelve el valor solo si es iterable
     */
    public function iterable(): ?iterable{
        return is_a($this->val, Traversable::class)?$this->val:$this->array();
    }

    /**
     * @return iterable|null Devuelve el valor si es iterable. Si el valor es un objeto no {@see Traversable}, se convierte en un array
     * @see DataGetter::array_like()
     */
    public function iterable_like(): ?iterable{
        return is_a($this->val, Traversable::class)?$this->val:$this->array_like();
    }

    private static function array_is_list(array $array){
        $i=0;
        foreach($array AS $k=>$v){
            if($k!==$i++) return false;
        }
        return true;
    }

    public function array_list(): ?array{
        if(is_array($this->val) && self::array_is_list($this->val)) return $this->val;
        return null;
    }

    public function array(): ?array{
        if(is_array($this->val)) return $this->val;
        return null;
    }

    public function object(): ?object{
        if(is_object($this->val)) return $this->val;
        return null;
    }

    /**
     * @return array|null Si el valor es un objeto, se convierte en un array
     */
    public function array_like(): ?array{
        if(is_array($this->val)) return $this->val;
        if(is_object($this->val)) return get_object_vars($this->val);
        return null;
    }

    /**
     * @return object|null Si el valor es un array, se convierte en un objeto
     */
    public function object_like(): ?object{
        if(is_array($this->val)) return (object)$this->val;
        if(is_object($this->val)) return $this->val;
        return null;
    }

    /**
     * Obtiene una ruta dentro del valor actual
     * @param string $path Ruta de multiples niveles
     * @param string $splitter Separador de la rutas. Default: "/"
     * @return $this
     */
    public function path(string $path, string $splitter='/'): self{
        if($splitter==='') return new self(null);
        return $this(...explode($splitter, $path));
    }

    /**
     * Obtiene la ruta de índices dentro del valor actual
     * @param string ...$index
     * @return $this
     */
    public function __invoke(string ...$index): self{
        $val=$this->val;
        foreach($index as $name){
            if($val===null) break;
            if(is_array($val)){
                $val=($val[$name] ?? null);
                continue;
            }
            if(is_object($val)){
                $val=($val->$name ?? null);
                continue;
            }
            $val=null;
        }
        return new self($val);
    }

    const IS_NOT_NULL=0;
    const IS_INT=1;
    const IS_FLOAT_INF=2;
    const IS_FLOAT=3;
    const IS_NUMERIC_INF=4;
    const IS_NUMERIC=5;
    const IS_BOOL=6;
    const IS_SCALAR=7;
    const IS_STRING=8;
    const IS_STRING_LIKE=9;
    const IS_RESOURCE=10;
    const IS_CALLABLE=11;
    const IS_ITERABLE=12;
    const IS_ARRAY=13;
    const IS_ARRAY_LIKE=14;
    const IS_OBJECT=15;
    const IS_OBJECT_LIKE=16;
    const IS_ITERABLE_LIKE=17;
    const IS_ARRAY_LIST=18;

    private const MATCH_LIST=[
        self::IS_NOT_NULL=>'val',
        self::IS_INT=>'int',
        self::IS_FLOAT_INF=>'float_inf',
        self::IS_FLOAT=>'float',
        self::IS_NUMERIC_INF=>'numeric_inf',
        self::IS_NUMERIC=>'numeric',
        self::IS_BOOL=>'bool',
        self::IS_SCALAR=>'scalar',
        self::IS_STRING=>'string',
        self::IS_STRING_LIKE=>'string_like',
        self::IS_RESOURCE=>'resource',
        self::IS_CALLABLE=>'callable',
        self::IS_ITERABLE=>'iterable',
        self::IS_ARRAY=>'array',
        self::IS_ARRAY_LIKE=>'array_like',
        self::IS_OBJECT=>'object',
        self::IS_OBJECT_LIKE=>'object_like',
        self::IS_ITERABLE_LIKE=>'iterable_like',
        self::IS_ARRAY_LIST=>'array_list',
    ];

    /**
     * Devuelve el nombre de la primera propiedad en la estructura que coincida con el filtro indicado
     * @param int $match Filtro de búsqueda. Valores en constantes {@see DataGetter}::IS_* como {@see DataGetter::IS_STRING}
     * @param string ...$names Nombres a analizar
     * @return string|null
     */
    public function index_match(int $match, string ...$names): ?string{
        $method=self::MATCH_LIST[$match] ?? null;
        if(!$method) return null;
        foreach($names as $name){
            if(call_user_func([
                    $this->$name,
                    $method
                ])!==null) return $name;
        }
        return null;
    }

    /**
     * Devuelve el nombre de la primera propiedad en la estructura que no coincida con el filtro indicado
     * @param int $match Filtro de búsqueda. Valores en constantes {@see DataGetter}::IS_* como {@see DataGetter::IS_STRING}
     * @param string ...$names Nombres a analizar
     * @return string|null
     */
    public function index_mismatch(int $match, string ...$names): ?string{
        $method=self::MATCH_LIST[$match] ?? null;
        if(!$method) return null;
        foreach($names as $name){
            if(call_user_func([
                    $this->$name,
                    $method
                ])===null) return $name;
        }
        return null;
    }

    /**
     * Devuelve el nombre de la primera ruta en la estructura que coincida con el filtro indicado
     * @param int $match Filtro de búsqueda. Valores en constantes {@see DataGetter}::IS_* como {@see DataGetter::IS_STRING}
     * @param string ...$paths Rutas a analizar {@see DataGetter::path()}
     * @return string|null
     */
    public function path_match(int $match, string ...$paths): ?string{
        $method=self::MATCH_LIST[$match] ?? null;
        if(!$method) return null;
        foreach($paths as $path){
            if(call_user_func([
                    $this->path($path),
                    $method
                ])!==null) return $path;
        }
        return null;
    }

    /**
     * Devuelve el nombre de la primera ruta en la estructura que no coincida con el filtro indicado
     * @param int $match Filtro de búsqueda. Valores en constantes {@see DataGetter}::IS_* como {@see DataGetter::IS_STRING}
     * @param string ...$paths Rutas a analizar {@see DataGetter::path()}
     * @return string|null
     */
    public function path_mismatch(int $match, string ...$paths): ?string{
        $method=self::MATCH_LIST[$match] ?? null;
        if(!$method) return null;
        foreach($paths as $path){
            if(call_user_func([
                    $this->path($path),
                    $method
                ])===null) return $path;
        }
        return null;
    }

    public function __get($name): self{
        if($this->val===null) return $this;
        if(is_array($this->val)) return new self($this->val[$name] ?? null);
        if(is_object($this->val)) return new self($this->val->$name ?? null);
        return new self(null);
    }

    public function __isset($name): bool{
        if($this->val===null) return false;
        if(is_array($this->val)) return isset($this->val[$name]);
        if(is_object($this->val)) return isset($this->val->$name);
        return false;
    }

    public function offsetGet($offset): self{
        return $this->__get($offset);
    }

    public function offsetExists($offset): bool{
        return $this->__isset($offset);
    }

    public function __toString(){
        return $this->string_like() ?? '';
    }

    public function __set($name, $value): void{
        trigger_error('Read-only properties', E_USER_NOTICE);
    }

    public function __unset($name): void{
        trigger_error('Read-only properties', E_USER_NOTICE);
    }

    public function offsetSet($offset, $value): void{
        trigger_error('Read-only properties', E_USER_NOTICE);
    }

    public function offsetUnset($offset): void{
        trigger_error('Read-only properties', E_USER_NOTICE);
    }

    public function getIterator(): Traversable{
        if(is_a($this->val, Traversable::class)) return $this->val;
        return new ArrayIterator($this->array_like() ?? []);
    }

    /**
     * @return mixed
     */
    public function jsonSerialize(): mixed{
        return $this->val();
    }

    public function __serialize(): array{
        return [$this->val()];
    }

    public function __unserialize(array $data): void{
        $this->_val($data[0] ?? null);
    }

    private static function _depth($data, int $max, int $level=0): int{
        if($data===null) return $level;
        $max_lvl=++$level;
        if($max_lvl>$max) return $max_lvl;
        if(!is_array($data) && !is_object($data)) return $max_lvl;
        foreach(get_object_vars((object)$data) AS $v){
            $lvl=static::_depth($v, $max, $level);
            if($lvl>$max_lvl){
                $max_lvl=$lvl;
                if($max_lvl>$max) return $max_lvl;
            }
        }
        return $max_lvl;
    }

    /**
     * Calcula la profundidad del valor actual con un limite en el conteo.
     *
     * Ejemplo: Si la profuncidad máxima indicada es 256, y el valor es recursivo (profundidad infinita), el resultado será 257
     * @param int $max_depth Default: 256. Establece una profundidad máxima en el conteo. Esto evita un conteo infinito por valores recursivos
     * @return int
     */
    public function count_depth(int $max_depth=256): int{
        return static::_depth($this->val, $max_depth);
    }

    private static function _convert($data, int $opt, int $max, array $last=[]){
        if($data===null || is_scalar($data)) return $data;
        if(!is_array($data) && !is_object($data)) return null;
        if(in_array($data, $last, true)) return null;
        if(($opt & self::OPT_TO_STRING) && is_string($new=static::obj2string($data))) return $new;
        $last[]=&$data;
        if(--$max<=0) return ($opt & self::OPT_KEEP_EXCEEDED)?$data:null;
        $new=array_map(function($v) use ($opt, $max, $last){
            return static::_convert($v, $opt, $max, $last);
        }, is_array($data)?$data:get_object_vars($data));
        if(($opt & self::OPT_VACUUM)){
            $new=array_filter($new, function($v){ return !is_null($v); });
            if(count($new)===0) return null;
        }
        if(!($opt & self::OPT_TO_ARRAY) && is_object($data)) $new=(object)$new;
        return $new;
    }

    /**
     * Convierte los object en array
     * @see DataGetter::convert()
     */
    const OPT_TO_ARRAY=1<<0;
    /**
     * Elimina los array y object vacios, y los valores null
     * @see DataGetter::convert()
     */
    const OPT_VACUUM=1<<1;
    /**
     * Convierte los objetos en string cuando este lo permite
     *
     * Esta opción se ejecuta antes que {@see DataGetter::OPT_TO_ARRAY}
     * @see DataGetter::convert()
     */
    const OPT_TO_STRING=1<<2;
    /**
     * Hace que se conserven los valores excedentes originales en el resultado (la profundidad de {@see DataGetter::convert()} ya no trunca el resultado)
     *
     * ## CUIDADO: Esto puede provocar que no se eliminen valores resursivos o tipos de datos no admitidos por la función de conversión
     */
    const OPT_KEEP_EXCEEDED=1<<3;

    /**
     * ## Importante: Al convertir los datos, se pierden algunos tipos no admitidos como los resource, y comportamientos especiales de objetos como la conversión automática a string (por ejemplo los object {@see GMP})
     * Crea una copia conservando solo valores de tipo scalar, null, array y object, si no es permitido el valor se convierte en NULL
     *
     * Los objetos se convierten en stdClass, si no se usa {@see DataGetter::OPT_TO_ARRAY}
     *
     * Los valores de tipo array y object se rastrean para truncar la recursividad
     * @param int $options Opciones definidas por la constantes {@see DataGetter}::OPT_*. ejemplo para limpiar null y convertir los objetos en array: DataGetter::OPT_VACUUM|DataGetter::OPT_TO_ARRAY
     * @param int $max_depth Default: 256. Establece una profundidad máxima que trunca el resultado. Ver {@see DataGetter::OPT_KEEP_EXCEEDED}
     * @return $this
     * @see DataGetter::count_depth()
     */
    public function convert(int $options=0, int $max_depth=256): self{
        return new self(static::_convert($this->val, $options, $max_depth));
    }
}
