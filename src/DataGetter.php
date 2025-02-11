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
    protected $val;

    public function __construct($value=null){
        if($value!==null) $this->set($value);
    }
    
    protected function set($value){
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
     * Returns {@see gettype()}
     * @return string
     */
    public function type(): string{
        return gettype($this->val);
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
     * @return float|null
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
     * @return float|int|string|null
     * @see is_numeric()
     * @see DataGetter::string_like()
     */
    public function numeric_inf(){
        $val=static::obj2string($this->val) ?? $this->val;
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
     * Si el valor es un escalar o un objeto con el magic method `__toString`, se convierte en string con {@see strval()}
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

    private static function array_is_list(array $array): bool{
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
     * @param string $path Ruta de multiples niveles. El separador de la rutas: "/"
     * @return $this
     */
    public function path(string $path){
        return $this->route(...explode('/', $path));
    }

    /**
     * Obtiene la ruta de índices dentro del valor actual
     * @param string ...$index
     * @return $this
     */
    public function route(string ...$index){
        $res=$this;
        foreach($index as $name){
            $res=$res->$name;
        }
        return $res;
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

    /**
     * @param $name
     * @return $this
     */
    public function __get($name){
        $res=new static();
        if(is_array($this->val) && isset($this->val[$name])){
            $res->val=&$this->val[$name];
        }
        if(is_object($this->val) && isset($this->val->$name)){
            $res->val=&$this->val->$name;
        }
        return $res;
    }

    public function __isset($name): bool{
        if($this->val===null) return false;
        if(is_array($this->val)) return isset($this->val[$name]);
        if(is_object($this->val)) return isset($this->val->$name);
        return false;
    }

    /**
     * @param $offset
     * @return $this
     */
    public function offsetGet($offset){
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
        $this->__set($offset, $value);
    }

    public function offsetUnset($offset): void{
        $this->__unset($offset);
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
        $this->set($data[0] ?? null);
    }

    private static function _depth($data, int $max): int{
        if($data===null) return 0;
        $level=1;
        if($max<$level) return $level;
        if(!is_array($data) && !is_object($data)) return $level;
        $maxN=$max-1;
        foreach(get_object_vars((object)$data) AS $v){
            if($v===null) continue;
            if(is_array($v) || is_object($v)){
                $lvl=static::_depth($v, $maxN)+1;
            }
            else{
                $lvl=2;
            }
            if($lvl>$level){
                if($max<$lvl) return $lvl;
                $level=$lvl;
            }
        }
        return $level;
    }

    /**
     * Calcula la profundidad del valor actual con un limite en el conteo.
     *
     * Ejemplo: Si la profuncidad máxima indicada es 256, y el valor es recursivo (profundidad infinita), el resultado será 257
     * @param int $max_depth Default: 256. Establece una profundidad máxima en el conteo. Esto evita errores de iteración por valores recursivos
     * @return int
     */
    public function count_depth(int $max_depth=256): int{
        return static::_depth($this->val, $max_depth);
    }

    /**
     * ## Importante: Al convertir los datos, se pierden algunos tipos no admitidos como los resource, y comportamientos especiales de objetos como la conversión automática a string (por ejemplo los object {@see GMP})
     * Crea una copia conservando solo valores de tipo scalar, null, array y object, si no es permitido el valor se convierte en NULL
     *
     * Los objetos se convierten en stdClass, si no se usa {@see DataGetter::OPT_TO_ARRAY}
     *
     * Los valores de tipo array y object se rastrean para truncar la recursividad
     * @param int $options Opciones definidas por la constantes {@see DataGetter}::OPT_*. Ejemplo para limpiar vacíos y convertir los objetos en array: {@see DataGetter::OPT_VACUUM}|{@see DataGetter::OPT_TO_ARRAY}
     * @param int $max_depth Default: 256. Establece una profundidad máxima que trunca el resultado. Ver {@see DataGetter::OPT_IGNORE_RECURSION}
     * @return $this
     * @see DataGetter::count_depth()
     */
    public function convert(int $options=0, int $max_depth=256){
        $arr_rec=$obj_rec=null;
        if(!($options & self::OPT_IGNORE_RECURSION)){
            $arr_rec=preg_match('/=> Array\r?\n \*RECURSION\*\r?\n/', print_r($this->val, true))>0?[]:null;
            $obj_rec=preg_match('/=> .+ Object\r?\n \*RECURSION\*\r?\n/', print_r($this->val, true))>0?[]:null;
        }
        $res=new static(static::_convert($this->val, $arr_rec, $obj_rec, $options, $max_depth));
        return $res;
    }

    private static function _convert($data, ?array $arr_recursion, ?array $obj_recursion, int $opt, int $max){
        if($data===null || is_scalar($data)) return $data;
        if(!is_array($data) && !is_object($data)) return null;
        if($arr_recursion!==null && is_array($data) && in_array($data, $arr_recursion, true)) return null;
        elseif($obj_recursion!==null && is_object($data) && in_array($data, $obj_recursion, true)) return null;
        if(($opt & self::OPT_TO_STRING) && is_string($new=static::obj2string($data))) return $new;
        $new=[];
        --$max;
        if($max>0){
            if($arr_recursion!==null && is_array($data)) $arr_recursion[]=&$data;
            elseif($obj_recursion!==null && is_object($data)) $obj_recursion[]=&$data;
            foreach(is_array($data)?$data:get_object_vars($data) as $k=>$v){
                $new[$k]=static::_convert($v, $arr_recursion, $obj_recursion, $opt, $max);
            }
        }
        /* */
        if(($opt & self::OPT_VACUUM)){
            $new=array_filter($new, function($v){ return !is_null($v); });
            if(count($new)===0) return null;
        }
        if(!($opt & self::OPT_TO_ARRAY) && is_object($data)) $new=(object)$new;
        return $new;
    }

    /**
     * Elimina los array y object vacios, y los valores null
     * @see DataGetter::convert()
     */
    const OPT_VACUUM=1;
    /**
     * Convierte los object en array
     * @see DataGetter::convert()
     */
    const OPT_TO_ARRAY=2;
    /**
     * Convierte los objetos en string cuando este lo permite
     *
     * Esta opción se ejecuta antes que {@see DataGetter::OPT_TO_ARRAY}
     * @see DataGetter::convert()
     */
    const OPT_TO_STRING=4;
    /**
     * Provoca que se ignore la detección y eliminación de valores recursivos en el resultado (la profundidad de {@see DataGetter::convert()} es lo único que trunca el resultado)
     *
     * ## CUIDADO: Esto puede provocar que se conserve una gran cantidad de datos repetidos, si existe recursividad
     */
    const OPT_IGNORE_RECURSION=8;
}
