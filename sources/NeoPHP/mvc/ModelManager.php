<?php

namespace NeoPHP\mvc;

use Exception;
use NeoPHP\app\ApplicationComponent;
use NeoPHP\core\Collection;
use NeoPHP\core\reflect\ReflectionAnnotatedClass;
use NeoPHP\sql\Connection;
use NeoPHP\util\IntrospectionUtils;
use NeoPHP\util\logging\Logger;
use NeoPHP\util\memory\MemCache;
use NeoPHP\util\properties\PropertiesManager;
use stdClass;

abstract class ModelManager extends ApplicationComponent
{
    const ANNOTATION_ENTITY = "entity";
    const ANNOTATION_ATTRIBUTE = "attribute";
    const ANNOTATION_ID = "id";
    const ANNOTATION_PARAMETER_NAME = "name";
    
    private static $modelMetadata = [];
    private static $connections = [];
    private static $cacheConnections = [];
    
    public function __construct (MVCApplication $application)
    {
        parent::__construct($application);
    }
    
    /**
     * Obtiene el manager de propiedades de la aplicación
     * @return PropertiesManager Propiedades de la aplicación
     */
    protected final function getProperties ()
    {
        return $this->application->getProperties();
    }
    
    /**
     * Obtiene el logger de la aplicación
     * @return Logger Logger de la aplicación
     */
    protected final function getLogger ()
    {
        return $this->application->getLogger();
    }
    
    /**
     * Obtiene el manejador de modelos
     * @param type $managerClass
     * @return ModelManager Manejador de modelos
     */
    protected final function getManager ($managerClass)
    {
        return $this->application->getManager($managerClass);
    }
    
    /**
     * Obtiene una nueva conexión de base de datos en funcion del nombre especificado
     * @param string $connectionName Nombre de la conexión que se desea obtener
     * @return Connection conexión de base de datos
     */
    public function getConnection ($connectionName=null)
    {
        if (!isset($connectionName))
            $connectionName = "main";
        
        if (!isset(self::$connections[$connectionName]))
        {
            if (!isset($this->getProperties()->connections))
                throw new Exception ("Property \"connections\" not found !!");
            
            $connectionConfig = null; 
            if (is_object($this->getProperties()->connections))
            {
                $connectionConfig = $this->getProperties()->connections->$connectionName;
            }
            else
            {
                foreach ($this->getProperties()->connections as $testConnectionProperty)
                {
                    if ($testConnectionProperty->name = $connectionName)
                    {
                        $connectionConfig = $testConnectionProperty;
                        break;
                    }
                }
            }
            if (!isset($connectionConfig))
                throw new Exception ("Connection \"$connectionName\" not found !!");

            $connection = new Connection();
            $connection->setLogger($this->getLogger());
            $connection->setDriver($connectionConfig->driver);
            $connection->setDatabase($connectionConfig->database);
            $connection->setHost(isset($connectionConfig->host)? $connectionConfig->host : "localhost");
            $connection->setPort(isset($connectionConfig->port)? $connectionConfig->port : "");
            $connection->setUsername(isset($connectionConfig->username)? $connectionConfig->username : "");
            $connection->setPassword(isset($connectionConfig->password)? $connectionConfig->password : "");
            self::$connections[$connectionName] = $connection;
        }
        return self::$connections[$connectionName];
    }
    
    /**
     * Obtiene el manejador de cache asociado a la aplicación
     * @return MemCache Manejador de cache
     */
    public function getCacheConnection ($connectionName=null)
    {
        if (!isset($connectionName))
            $connectionName = "main";
        
        if (!isset(self::$cacheConnections[$connectionName]))
        {
            $connectionConfig = null; 
            if (isset($this->getProperties()->cacheConnections))
            {
                if (is_object($this->getProperties()->cacheConnections))
                {
                    $connectionConfig = $this->getProperties()->cacheConnections->$connectionName;
                }
                else
                {
                    foreach ($this->getProperties()->cacheConnections as $testConnectionProperty)
                    {
                        if ($testConnectionProperty->name = $connectionName)
                        {
                            $connectionConfig = $testConnectionProperty;
                            break;
                        }
                    }
                }
            }
            
            self::$cacheConnections[$connectionName] = (isset($connectionConfig))? (new MemCache($connectionConfig->host, $connectionConfig->port)) : (new MemCache());
        }
        return self::$cacheConnections[$connectionName];
    }
    
    /**
     * Crea un modelo con las propiedades establecidas
     * @param type $modelClass
     * @param type $modelProperties
     * @return Model Modelo creado
     */
    protected final function createModel ($modelClass, $modelProperties)
    {
        $model = null;
        if ($modelProperties != null)
        {
            $model = new $modelClass;
            $model->setFrom($modelProperties);
        }
        return $model;
    }
    
    /**
     * Crea una colección de modelos
     * @param type $modelClass
     * @param array $modelsProperties
     * @return Collection Coleccion de modelos
     */
    protected final function createModelCollection ($modelClass, array $modelsProperties)
    {
        $models = new Collection();
        foreach ($modelsProperties as $modelProperties)
        {
            $models->add($this->createModel($modelClass, $modelProperties));
        }
        return $models;
    }
    
    protected function getModel ($modelClassName, $id, $connectionName="main")
    {
        $model = null;
        $modelFields = $this->getConnection($connectionName)->createQuery($this->getModelTable($modelClassName))->addWhere($this->getModelIdField($modelClassName), "=", $id)->getFirst();
        if (!empty($modelFields))
        {
            $model = new $modelClassName;
            $this->setModelFields($model, $modelFields);
        }
        return $model;
    }
    
    protected function getAllModels ($modelClassName, $connectionName="main")
    {
        $models = new Collection();
        $modelsData = $this->getConnection($connectionName)->createQuery($this->getModelTable($modelClassName))->addOrderBy($this->getModelIdField($modelClassName))->get();
        foreach ($modelsData as $modelFields)
        {
            $model = new $modelClassName;
            $this->setModelFields($model, $modelFields);
            $models->add($model);
        }
        return $models;
    }
    
    protected function insertModel (Model $model, $connectionName="main")
    {
        $modelClassName = get_class($model);
        $modelFields = $this->getModelFields($model);
        $modelIdField = $this->getModelIdField($modelClassName);
        unset($modelFields[$modelIdField]);
        $this->getConnection($connectionName)->createQuery($this->getModelTable($modelClassName))->insert($modelFields);
    }
    
    protected function updateModel (Model $model, $connectionName="main")
    {
        $modelClassName = get_class($model);
        $modelFields = $this->getModelFields($model);
        $modelIdField = $this->getModelIdField($modelClassName);
        $modelId = $modelFields[$modelIdField];
        $savedModelFields = $this->getConnection($connectionName)->createQuery($this->getModelTable($modelClassName))->addWhere($modelIdField, "=", $modelId)->getFirst();
        $updateModelFields = array_diff_assoc($modelFields, $savedModelFields);
        if (!empty($updateModelFields))
            $this->getConnection($connectionName)->createQuery($this->getModelTable($modelClassName))->addWhere($modelIdField, "=", $modelId)->update($updateModelFields);
    }
    
    protected function persistModel (Model $model, $connectionName="main")
    {
        $modelFields = $this->getModelFields($model);
        $modelIdField = $this->getModelIdField(get_class($model));
        $modelId = $modelFields[$modelIdField];
        if (!empty($modelId))
        {
            $this->updateModel ($model, $connectionName);
        }
        else
        {
            $this->insertModel($model, $connectionName);
        }
    }
    
    protected function deleteModel (Model $model, $connectionName="main")
    {
        $modelFields = $this->getModelFields($model);
        $modelIdField = $this->getModelIdField(get_class($model));
        $modelId = $modelFields[$modelIdField];
        $this->getConnection($connectionName)->createQuery($this->getModelTable(get_class($model)))->addWhere($modelIdField, "=", $modelId)->delete();
    }
    
    protected function completeModel (Model $model, $connectionName="main")
    {
        $modelClassName = get_class($model);
        $modelFields = $this->getModelFields($model);
        $modelIdField = $this->getModelIdField($modelClassName);
        $modelId = $modelFields[$modelIdField];
        $newModelFields = $this->getConnection($connectionName)->createQuery($this->getModelTable($modelClassName))->addWhere($modelIdField, "=", $modelId)->getFirst();
        if (!empty($newModelFields))
            $this->setModelFields($model, $newModelFields);
    }
    
    protected function getModelTable ($modelClassName)
    {
        $modelMetadata = $this->getModelMetadata($modelClassName);
        return $modelMetadata->name;
    }
    
    protected function getModelIdField ($modelClassName)
    {
        return $this->getModelMetadata($modelClassName)->idAttribute;
    }   
    
    protected function getModelFields (Model $model)
    {
        $modelFields = [];
        $modelMetadata = $this->getModelMetadata(get_class($model));
        foreach ($modelMetadata->attributes as $attribute)
        {
            $modelFields[$attribute->name] = IntrospectionUtils::getPropertyValue($model, $attribute->propertyName);
        }
        return $modelFields;
    }
    
    protected function setModelFields (Model $model, array $fields)
    {
        $modelFields = [];
        $modelMetadata = $this->getModelMetadata(get_class($model));
        foreach ($modelMetadata->attributes as $attribute)
        {
            IntrospectionUtils::setPropertyValue($model, $attribute->propertyName, $fields[$attribute->name]);
        }
        return $modelFields;
    }
    
    protected function createModelFromFields ($modelClassName, array $fields)
    {
        $model = null;
        if (!empty($fields))
        {
            $model = new $modelClassName;
            $this->setModelFields($model, $fields);
        }
        return $model;
    }
    
    protected function getModelMetadata ($modelClassName)
    {
        if (empty(self::$modelMetadata[$modelClassName]))
            self::$modelMetadata[$modelClassName] = $this->retrieveEntityMetadata($modelClassName);
        return self::$modelMetadata[$modelClassName];
    }
    
    protected function retrieveEntityMetadata ($entityClassName)
    {
        $entityMetadata = new stdClass();
        $entityClass = new ReflectionAnnotatedClass($entityClassName);
        $entityAnnotation = $entityClass->getAnnotation(self::ANNOTATION_ENTITY);
        if ($entityAnnotation == null)
            throw new Exception ("Entity class \"$entityClassName\" must have the \"" . self::ANNOTATION_ENTITY . "\" annotation");
        $entityName = $entityAnnotation->getParameter(self::ANNOTATION_PARAMETER_NAME);
        if (empty($entityName))
            $entityName = strtolower($entityClass->getShortName());
        $entityMetadata->name = $entityName; 
        $entityMetadata->attributes = [];
        $properties = $entityClass->getProperties();
        foreach ($properties as $property)
        {
            $attributeAnnotation = $property->getAnnotation(self::ANNOTATION_ATTRIBUTE);
            if ($attributeAnnotation != null)
            {
                $attribute = new stdClass();
                $attributeName = $attributeAnnotation->getParameter(self::ANNOTATION_PARAMETER_NAME);
                if (empty($attributeName))
                    $attributeName = strtolower($property->getName());
                $attribute->name = $attributeName;
                $attribute->propertyName = $property->getName();
                $entityMetadata->attributes[] = $attribute;
                
                $idAnnotation = $property->getAnnotation(self::ANNOTATION_ID);
                if ($idAnnotation)
                {
                    $entityMetadata->idAttribute = $attributeName;
                }
            }
        }
        return $entityMetadata;
    }
}