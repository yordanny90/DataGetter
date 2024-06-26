<?php

/**
 * Repositorio {@link https://github.com/yordanny90/DataGetter}
 *
 * > Requiere PHP 7.1+, 8.0+
 *
 * Brinda varias formas de obtener valores y evitar errores:
 * ```php
 * $data=new DataGetter($_POST);
 * $r1=$data->A->B->C->val();
 * $r2=$data['A']['B']['C']->val();
 * $r3=$data->path('A/B/C')->val();
 * // Los valores de $r1, $r2 y $r3 son iguales
 * ```
 *
 * - Tiene funciones para obtener el valor solo si el tipo de dato es el esperado, de lo contrario devuelve `null`
 * - Tiene funciones para encontrar el primer valor que cumpla con el criterio de tipo de dato
 * - Un objeto {@see DataGetter} se comporta como `string` si es necesario, el valor es generado por {@see DataGetter::string_like()}, pero en caso de `null` se convierte en un string vacío ("")
 */
class DataGetter implements ArrayAccess{
    private $val;

    public function __construct($v){
        $this->val=$v;
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
        $val=$this->obj2string() ?? $this->val;
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
        $val=$this->obj2string() ?? $this->val;
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

    private function obj2string(): ?string{
        if(is_object($this->val)){
            try{
                return strval($this->val);
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
        return $this->obj2string();
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
     * @return iterable|null
     */
    public function iterable(): ?iterable{
        if(!is_iterable($this->val)) return $this->val;
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

    public function array_like(): ?array{
        if(is_array($this->val)) return $this->val;
        if(is_object($this->val)) return get_object_vars($this->val);
        return null;
    }

    public function object_like(): ?object{
        if(is_array($this->val)) return (object)$this->val;
        if(is_object($this->val)) return $this->val;
        return null;
    }

    public function is_a(string $class): bool{
        return is_a($this->val, $class);
    }

    public function class_(string $class): ?object{
        return $this->is_a($class)?$this->val:null;
    }

    /**
     * Devuelve el nombre de la primera propiedad en la estructura que exista
     * @param string ...$names Nombres a analizar
     * @return string|null
     */
    public function index_exists(string ...$names): ?string{
        foreach($names as $name){
            if($this->__isset($name)) return $name;
        }
        return null;
    }

    /**
     * Devuelve el nombre de la primera propiedad en la estructura que no exista
     * @param string ...$names Nombres a analizar
     * @return string|null
     */
    public function index_missing(string ...$names): ?string{
        foreach($names as $name){
            if(!$this->__isset($name)) return $name;
        }
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
        $index=explode($splitter, $path);
        $val=$this->val;
        foreach($index as $name){
            if($val===null) break;
            if(is_array($val)){
                $val=($val[$name] ?? null);
                continue;
            }
            if(is_object($this->val)){
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
    ];

    /**
     * Devuelve el nombre de la primera propiedad en la estructura que coincida con el filtro indicado
     * @param int $match Filtro de búsqueda. Valores en constantes {@see DataGetter}::IS_* como {@see DataGetter::IS_STRING}
     * @param string ...$names Nombres a analizar
     * @return string|null
     */
    public function index_match(int $match, string ...$names): ?string{
        $method=static::MATCH_LIST[$match] ?? null;
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
        $method=static::MATCH_LIST[$match] ?? null;
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
        $method=static::MATCH_LIST[$match] ?? null;
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
        $method=static::MATCH_LIST[$match] ?? null;
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
        if(is_array($this->val)){
            return new self($this->val[$name] ?? null);
        }
        if(is_object($this->val)){
            return new self($this->val->$name ?? null);
        }
        return new self(null);
    }

    public function __isset($name): bool{
        if($this->val===null) return false;
        if(is_array($this) && array_key_exists($name, $this->val)){
            return true;
        }
        if(is_object($this->val) && property_exists($this->val, $name) && array_key_exists($name, $this->array_like())){
            return true;
        }
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
        // Solo lectura
    }

    public function offsetSet($offset, $value): void{
        // Solo lectura
    }

    public function offsetUnset($offset): void{
        // Solo lectura
    }
}
