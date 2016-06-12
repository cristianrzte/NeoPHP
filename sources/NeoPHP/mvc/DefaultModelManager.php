<?php

namespace NeoPHP\mvc;

use Exception;
use MongoClient;
use MongoDB;
use MongoId;
use NeoPHP\core\Collection;
use NeoPHP\core\reflect\ReflectionAnnotatedClass;
use NeoPHP\sql\Connection;
use NeoPHP\sql\ConnectionQueryColumnFilter;
use NeoPHP\sql\ConnectionQueryFilterGroup;
use NeoPHP\util\IntrospectionUtils;
use PDO;
use stdClass;

class DefaultModelManager extends ModelManager
{
    const PARAMETER_START = "start";
    const PARAMETER_LIMIT = "limit";
    
    const ANNOTATION_ENTITY = "entity";
    const ANNOTATION_ATTRIBUTE = "attribute";
    const ANNOTATION_ID = "id";
    const ANNOTATION_PARAMETER_NAME = "name";
    
    private static $databases = [];
    
    private $modelMetadata;
    
    /**
     * Obtiene una nueva conexión de base de datos en funcion del nombre especificado
     * @param string $databaseName Nombre de la conexión que se desea obtener
     * @return Connection|MongoDB conexión de datos
     */
    protected function getDatabase ($databaseName=null)
    {
        if (!isset($databaseName))
            $databaseName = isset($this->getProperties()->defaultDatabase)? $this->getProperties()->defaultDatabase : "main";
        
        if (!isset(self::$databases[$databaseName]))
        {
            if (!isset($this->getProperties()->connections))
                throw new Exception ("Property \"connections\" not found !!");
            
            $connectionConfig = null; 
            if (is_object($this->getProperties()->connections))
            {
                $connectionConfig = $this->getProperties()->connections->$databaseName;
            }
            else
            {
                foreach ($this->getProperties()->connections as $testConnectionProperty)
                {
                    if ($testConnectionProperty->name = $databaseName)
                    {
                        $connectionConfig = $testConnectionProperty;
                        break;
                    }
                }
            }
            if (!isset($connectionConfig))
                throw new Exception ("Connection \"$databaseName\" not found !!");

            $database = null;
            switch ($connectionConfig->driver)
            {
                case "mongodb":
                    $mongoHost = isset($connectionConfig->host)? $connectionConfig->host : MongoClient::DEFAULT_HOST;
                    $mongoPort = isset($connectionConfig->port)? $connectionConfig->port : MongoClient::DEFAULT_PORT;
                    $mongoDatabase = $connectionConfig->database;
                    $mongoConnectString = "mongodb://$mongoHost:$mongoPort";
                    $mongoClient = new MongoClient($mongoConnectString);
                    $database = $mongoClient->selectDB($mongoDatabase);
                    break;
                default:
                    $database = new Connection();
                    $database->setLogger($this->getLogger());
                    $database->setDriver($connectionConfig->driver);
                    $database->setDatabase($connectionConfig->database);
                    $database->setHost(isset($connectionConfig->host)? $connectionConfig->host : "localhost");
                    $database->setPort(isset($connectionConfig->port)? $connectionConfig->port : "");
                    $database->setUsername(isset($connectionConfig->username)? $connectionConfig->username : "");
                    $database->setPassword(isset($connectionConfig->password)? $connectionConfig->password : "");
                    break;
            }
            
            self::$databases[$databaseName] = $database;
        }
        return self::$databases[$databaseName];
    }
    
    /**
     * Obtiene el nombre de la entidad asociada con el modelo
     * @return string Nombre de entidad
     */
    protected function getModelEntityName ()
    {
        return $this->getModelMetadata()->name;
    }
    
    /**
     * Obtiene el nombre del atributo marcado como ID en el modelo
     * @return string nombre del atributo id del modelo
     */
    protected function getModelIdAttribute ()
    {
        return $this->getModelMetadata()->idAttribute;
    }   
    
    /**
     * Obtiene todos los atributos con sus valores del modelo
     * @param Model $model Modelo a obtener sus atributos
     * @return array Lista de atributos del modelo
     */
    protected function getModelAttributes (Model $model)
    {
        $modelAttributes = [];
        $modelMetadata = $this->getModelMetadata();
        foreach ($modelMetadata->attributes as $attribute)
        {
            $modelAttributes[$attribute->name] = IntrospectionUtils::getPropertyValue($model, $attribute->propertyName);
        }
        return $modelAttributes;
    }
    
    /**
     * Establece al modelo los atributos especificados
     * @param Model $model Modelo a establecer atributos
     * @param array $attributes Atributos a establecer
     */
    protected function setModelAttributes (Model $model, array $attributes)
    {
        $modelMetadata = $this->getModelMetadata();
        foreach ($modelMetadata->attributes as $attribute)
        {
            IntrospectionUtils::setPropertyValue($model, $attribute->propertyName, $attributes[$attribute->name]);
        }
    }
    
    /**
     * Crea un modelo nuevo a traves de atributos
     * @param array $attributes atributos a establecer
     * @return Model modelo creado
     */
    protected function createModelFromAttributes (array $attributes)
    {
        $model = null;
        if (!empty($attributes))
        {
            $modelClass = $this->getModelClass();
            $model = new $modelClass;
            $this->setModelAttributes($model, $attributes);
        }
        return $model;
    }
    
    /**
     * Obtiene metadatos de un modelo dado
     * @return type Metadatos del modelo
     */
    protected final function getModelMetadata ()
    {
        if (!isset($this->modelMetadata))
        {
            $this->modelMetadata = $this->retrieveModelMetadata();
        }
        return $this->modelMetadata;
    }
    
    /**
     * Obtiene de la clase del modelo los metadatos de entidad y atributos
     * @throws Exception Error si no se puede obtener los valores
     */
    protected function retrieveModelMetadata ()
    {
        $modelClass = $this->getModelClass();
        $entityMetadata = new stdClass();
        $entityClass = new ReflectionAnnotatedClass($modelClass);
        $entityAnnotation = $entityClass->getAnnotation(self::ANNOTATION_ENTITY);
        if ($entityAnnotation == null)
            throw new Exception ("Entity class \"$modelClass\" must have the \"" . self::ANNOTATION_ENTITY . "\" annotation");
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
    
    public function create(Model $model)
    {
        $createResult = false;
        $modelAttributes = $this->getModelAttributes($model);
        $modelIdAttribute = $this->getModelIdAttribute();
        unset($modelAttributes[$modelIdAttribute]);
        $database = $this->getDatabase();
        if ($database instanceof Connection)
        {
            $createResult = $database->createQuery($this->getModelEntityName())->insert($modelAttributes);
        }
        else if ($database instanceof MongoDB)
        {
            $createResult = $database->selectCollection($this->getModelEntityName())->insert($modelAttributes);
        }
        return $createResult;
    }

    public function delete(Model $model)
    {
        $deleteResult = false;
        $modelAttributes = $this->getModelAttributes($model);
        $modelIdAttribute = $this->getModelIdAttribute();
        $modelId = $modelAttributes[$modelIdAttribute];
        $database = $this->getDatabase();
        if ($database instanceof Connection)
        {
            $deleteResult = $database->createQuery($this->getModelEntityName())->addWhere($modelIdAttribute, "=", $modelId)->delete();
        }
        else if ($database instanceof MongoDB)
        {
            $deleteResult = $database->selectCollection($this->getModelEntityName())->remove(['_id'=>new MongoId($modelId)]);
        }
        return $deleteResult;
    }

    public function update(Model $model)
    {
        $updateResult = false;
        $modelAttributes = $this->getModelAttributes($model);
        $modelIdAttribute = $this->getModelIdAttribute();
        $modelId = $modelAttributes[$modelIdAttribute];
        $database = $this->getDatabase();
        if ($database instanceof Connection)
        {
            $modelQuery = $database->createQuery($this->getModelEntityName())->addWhere($modelIdAttribute, "=", $modelId);
            $savedModelAttributes = $modelQuery->getFirst();
            if (isset($savedModelAttributes))
            {
                $updateModelAttributes = array_diff_assoc($modelAttributes, $savedModelAttributes);
                if (!empty($updateModelAttributes))
                    $updateResult = $modelQuery->update($updateModelAttributes);
            }
        }
        else if ($database instanceof MongoDB)
        {
            $mongoCollection = $database->selectCollection($this->getModelEntityName());
            $mongoId = new MongoId($modelId);
            $document = $mongoCollection->findOne(['_id'=>$mongoId]);
            if (isset($document))
            {
                $savedModelAttributes = $this->getAttributesFromDocument($document);
                $updateModelAttributes = array_diff_assoc($modelAttributes, $savedModelAttributes);
                if (!empty($updateModelAttributes))
                    $updateResult = $mongoCollection->update (['_id'=>$mongoId], ['$set'=>$updateModelAttributes]);
            }
        }
        return $updateResult;
    }
    
    public function retrieve(ModelFilter $filters=null, ModelSorter $sorters=null, array $parameters=[])
    {
        $modelCollection = new Collection();
        $modelClass = $this->getModelClass();
        $database = $this->getDatabase();
        if ($database instanceof Connection)
        {
            $modelQuery = $database->createQuery($this->getModelEntityName());
            if (isset($filters))
            {
                $modelQuery->setWhereClause($this->getConnectionQueryFilter($filters));            
            }
            if (isset($sorters))
            {
                foreach ($sorters->getSorters() as $sorter)
                {
                    $modelQuery->addOrderBy($sorter->property, $sorter->direction);
                }
            }
            if (isset($parameters[self::PARAMETER_START]))
            {
                $modelQuery->setOffset($parameters[self::PARAMETER_START]);
            }
            if (isset($parameters[self::PARAMETER_LIMIT]))
            {
                $modelQuery->setLimit($parameters[self::PARAMETER_LIMIT]);
            }
            $modelQuery->get(PDO::FETCH_ASSOC, function ($modelAttributes) use ($modelCollection) 
            {
                $modelCollection->add($this->createModelFromAttributes($modelAttributes));
            });
        }
        else if ($database instanceof MongoDB)
        {
            $mongoCollection = $database->selectCollection($this->getModelEntityName());
            $mongoCursor = $mongoCollection->find();
            foreach ($mongoCursor as $document)
            {
                $modelCollection->add($this->createModelFromAttributes($this->getAttributesFromDocument($document)));
            }
        }
        return $modelCollection;
    }
    
    protected function getAttributesFromDocument ($document)
    {
        $modelIdAttribute = $this->getModelIdAttribute();
        $mongoId = strval($document["_id"]);
        $modelAttributes = $document;
        unset($modelAttributes["_id"]);
        $modelAttributes[$modelIdAttribute] = $mongoId;
        return $modelAttributes;
    }

    public function getConnectionQueryFilter (ModelFilter $modelFilter)
    {
        $filter = null;
        $modelMetadata = $this->getModelMetadata();
        
        if ($modelFilter instanceof PropertyModelFilter)
        {
            $propertyAttribute = null;
            foreach ($modelMetadata->attributes as $attribute) 
            {
                if ($attribute->propertyName == $modelFilter->getProperty())
                {
                    $propertyAttribute = $attribute->name;
                    break;
                }
            }
            if ($propertyAttribute == null)
            {
                throw new Exception ("Property \"" . $modelFilter->getProperty() . "\" not found in Model \"" . $this->getModelClass() . "\" !!");
            }
            
            $propertyOperator = PropertyModelFilter::OPERATOR_EQUALS;
            $propertyValue = $modelFilter->getValue();
            switch ($modelFilter->getOperator())
            {
                case PropertyModelFilter::OPERATOR_EQUALS: 
                    $propertyOperator = "="; 
                    break;
                case PropertyModelFilter::OPERATOR_NOT_EQUALS: 
                    $propertyOperator = "!="; 
                    break;
                case PropertyModelFilter::OPERATOR_CONTAINS: 
                    $propertyOperator = "like"; 
                    $propertyValue = "%$propertyValue%";
                    break;
                case PropertyModelFilter::OPERATOR_IN: 
                    $propertyOperator = "in"; 
                    break;
                case PropertyModelFilter::OPERATOR_GREATER_THAN: 
                    $propertyOperator = ">"; 
                    break;
                case PropertyModelFilter::OPERATOR_GREATER_OR_EQUALS_THAN: 
                    $propertyOperator = ">="; 
                    break;
                case PropertyModelFilter::OPERATOR_LESS_THAN: 
                    $propertyOperator = "<"; 
                    break;
                case PropertyModelFilter::OPERATOR_LESS_OR_EQUALS_THAN: 
                    $propertyOperator = "<="; 
                    break;
            }
            
            $filter = new ConnectionQueryColumnFilter($propertyAttribute, $propertyOperator, $propertyValue);
        }
        else if ($modelFilter instanceof ModelFilterGroup)
        {
            $filter = new ConnectionQueryFilterGroup();
            switch ($modelFilter->getConnector())
            {
                case ModelFilterGroup::CONNECTOR_AND: 
                    $filter->setConnector("AND");
                    break;
                case ModelFilterGroup::CONNECTOR_OR:
                    $filter->setConnector("OR");
                    break;                
            }
            foreach ($modelFilter->getFilters() as $childFilter)
            {
                $filter->addFilter($this->getConnectionQueryFilter($childFilter));
            }
        }
        return $filter;
    }
}