<?php
namespace BS\CLI\Model\CodeGenerator\Source;

use Camel\CaseTransformer;
use Camel\Format\SnakeCase;
use Camel\Format\StudlyCaps;
use BS\CLI\Model\AbstractCodeGenerator;
use BS\CLI\Model\CodeGenerator\AbstractFileGenerator;
use BS\CLI\Model\CodeGenerator\BaseWarningTrait;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag\MethodTag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\PropertyValueGenerator;
use Zend\Db\Metadata\Object\TableObject;

class SubModuleBaseController extends AbstractFileGenerator
{
    use BaseWarningTrait;

    protected $overwriteMode = self::OVERWRITE_MODE_ON;

    public function __construct(TableObject $table, $module = null, $parentClassNamespace = null)
    {
        parent::__construct();

        $this->table    = $table;
        $Transformer    = new CaseTransformer(new SnakeCase(), new StudlyCaps());
        $className      = $Transformer->transform($this->table->getName());
        $controllerName = $className . 'Controller';
        $this->setFilename($controllerName);

        $ClassGenerator = new ClassGenerator($controllerName);
        $ClassGenerator->setFlags(ClassGenerator::FLAG_ABSTRACT);
        if ($module) {
            $this->setNamespace($module . '\\' . AbstractCodeGenerator::NAMESPACE_CONTROLLER . '\Base');
        }

        if ($parentClassNamespace) {
            $this->setUse($parentClassNamespace . '\\' . AbstractCodeGenerator::NAMESPACE_CONTROLLER . '\\' .
                          $controllerName, 'BaseClass');
            $ClassGenerator->setExtendedClass('BaseClass');
        }

        $this->setUse($module . '\\' .
                      AbstractCodeGenerator::NAMESPACE_TABLEGATEWAY . '\\' .
                      $Transformer->transform($this->table->getName()), 'TableGateway');
        $this->setUse($module . '\\' .
                      AbstractCodeGenerator::NAMESPACE_MODEL . '\\' .
                      $Transformer->transform($this->table->getName()), 'Model');
        $this->setUse($module . '\Traits\AuthenticationTrait', 'AuthenticationTrait');
        $ClassGenerator->addTrait('AuthenticationTrait');

        $ClassGenerator->addProperty('modelClass',
                                     new PropertyValueGenerator(
                                         'Model::class',
                                         PropertyValueGenerator::TYPE_CONSTANT),
                                     PropertyGenerator::FLAG_PROTECTED);
        $ClassGenerator->addProperty('tableGatewayClass',
                                     new PropertyValueGenerator(
                                         'TableGateway::class',
                                         PropertyValueGenerator::TYPE_CONSTANT),
                                     PropertyGenerator::FLAG_PROTECTED);
        $ClassGenerator->setDocBlock(new DocBlockGenerator(null, null, [
            new MethodTag('getTableGateway()',
                          ['TableGateway'])
        ]));

        $this->setBody($this->getBaseWarningTrait() . str_repeat(self::LINE_FEED, 2) . $ClassGenerator->generate());
    }
}