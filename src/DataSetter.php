<?php

/**
 * Repositorio {@link https://github.com/yordanny90/DataGetter}
 *
 * > Requiere PHP 7.1+, 8.0+
 *
 * - Permite generar estructuras de datos de forma simple y segura
 * - Si se intenta signar una propiedad a un dato que no es object o array, el dato se reemplaza por un array automáticamente
 *
 * Brinda varias formas de asignar valores y evitar errores:
 * ```php
 * $data=new DataSetter();
 * $data->A->B->C='Valor';
 * $data['A']['B']['C']='Valor';
 * $data->path('A/B/C')->set('Valor');
 * $data('A','B','C')->set('Valor');
 * // En todos los casos las estructuras resultantes son iguales
 * ```
 *
 * Puede eliminar un dato con la funcion {@see unset}:
 * ```php
 * unset($data->A->B->C);
 * unset($data['A']['B']['C']);
 * ```
 */
class DataSetter extends DataGetter{
    /**
     * @var static|null
     */
    protected $parent;
    /**
     * @var string|null
     */
    protected $name;

    public function __construct(){
        parent::__construct();
    }

    /**
     * @return DataGetter
     */
    public function readonly(): DataGetter{
        return new DataGetter($this->val);
    }

    public function set($value){
        if($this->parent){
            $this->val=&$this->parent->fix($this->name);
            $this->parent=null;
            $this->name=null;
        }
        parent::set($value);
    }

    public function __get($name): static{
        $res=parent::__get($name);
        if($res->val===null){
            $res->parent=&$this;
            $res->name=$name;
        }
        return $res;
    }

    private function &fix($name): mixed{
        if($this->parent){
            $this->val=&$this->parent->fix($this->name);
            $this->parent=null;
            $this->name=null;
        }
        else{
            if(!is_array($this->val) && !is_object($this->val)) $this->val=[];
        }
        if(is_array($this->val)){
            if(!isset($this->val[$name])) $this->val[$name]=[];
            if(!is_array($this->val[$name]) && !is_object($this->val[$name])) $this->val[$name]=[];
            return $this->val[$name];
        }
        else{
            if(!isset($this->val->$name)) $this->val->$name=[];
            if(!is_array($this->val->$name) && !is_object($this->val->$name)) $this->val->$name=[];
            return $this->val->$name;
        }
    }

    public function __set($name, $value): void{
        if($this->parent){
            $this->val=&$this->parent->fix($this->name);
            $this->parent=null;
            $this->name=null;
        }
        else{
            if(!is_array($this->val) && !is_object($this->val)) $this->val=[];
        }
        if(is_a($value, DataGetter::class)) $value=$value->val;
        if(is_array($this->val)){
            if($name===null) $this->val[]=$value;
            else $this->val[$name]=$value;
        }
        else{
            if($name===null){
                $this->val=(array)$this->val;
                $this->val[]=$value;
            }
            else $this->val->$name=$value;
        }
    }

    public function __unset($name): void{
        if(is_array($this->val)){
            unset($this->val[$name]);
        }
        elseif(is_object($this->val)){
            unset($this->val->$name);
        }
    }

}