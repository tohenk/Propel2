<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Builder\Om;

use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Inheritance;

/**
 * Generates the empty stub object class for use with inheritance in the
 * user object model (OM).
 *
 * This class produces the empty stub class that can be customized with
 * application business logic, custom behavior, etc.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class MultiExtendObjectBuilder extends AbstractObjectBuilder
{
    /**
     * The current child "object" we are operating on.
     *
     * @var \Propel\Generator\Model\Inheritance|null
     */
    private $child;

    /**
     * Returns the name of the current class being built.
     *
     * @return string
     */
    public function getUnprefixedClassName(): string
    {
        return $this->getChild()->getClassName();
    }

    /**
     * Overrides method to return child package, if specified.
     *
     * @return string|null
     */
    public function getPackage(): ?string
    {
        return ($this->getChild()->getPackage() ?: parent::getPackage());
    }

    /**
     * Sets the child object that we're operating on currently.
     *
     * @param \Propel\Generator\Model\Inheritance $child Inheritance
     *
     * @return void
     */
    public function setChild(Inheritance $child): void
    {
        $this->child = $child;
    }

    /**
     * Returns the child object we're operating on currently.
     *
     * @throws \Propel\Generator\Exception\BuildException
     *
     * @return \Propel\Generator\Model\Inheritance
     */
    public function getChild(): Inheritance
    {
        if (!$this->child) {
            throw new BuildException('The MultiExtendObjectBuilder needs to be told which child class to build (via setChild() method) before it can build the stub class.');
        }

        return $this->child;
    }

    /**
     * Returns classpath to parent class.
     *
     * @return string|null
     */
    protected function getParentClasspath(): ?string
    {
        if ($this->getChild()->getAncestor()) {
            return $this->getChild()->getAncestor();
        }

        return $this->getObjectBuilder()->getClasspath();
    }

    /**
     * Returns classname of parent class.
     *
     * @return string|null
     */
    protected function getParentClassName(): ?string
    {
        return ClassTools::classname($this->getParentClasspath());
    }

    /**
     * Adds class phpdoc comment and opening of class.
     *
     * @param string $script The script will be modified in this method.
     *
     * @return void
     */
    protected function addClassOpen(string &$script): void
    {
        if ($this->getChild()->getAncestor()) {
            $ancestorClassName = $this->getChild()->getAncestor();
            if ($this->getDatabase()->hasTableByPhpName($ancestorClassName)) {
                $this->declareClassFromBuilder($this->getNewStubObjectBuilder($this->getDatabase()->getTableByPhpName($ancestorClassName)));
            } else {
                $this->declareClassNamespace($ancestorClassName, $this->getNamespace());
            }
        } else {
            $this->declareClassFromBuilder($this->getObjectBuilder());
        }
        $table = $this->getTable();
        $tableName = $table->getName();
        $tableDesc = $table->getDescription();

        if ($this->getBuildProperty('generator.objectModel.addClassLevelComment')) {
            $script .= "

/**
 * Skeleton subclass for representing a row from one of the subclasses of the '$tableName' table.
 *
 * $tableDesc
 *";
            if ($this->getBuildProperty('generator.objectModel.addTimeStamp')) {
                $now = date('r');
                $script .= "
 * This class was autogenerated by Propel " . $this->getBuildProperty('general.version') . " on:
 *
 * $now
 *";
            }
            $script .= "
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 */";
        }
        $script .= "
class " . $this->getUnqualifiedClassName() . ' extends ' . $this->getParentClassName() . "
{
";
    }

    /**
     * Specifies the methods that are added as part of the stub object class.
     *
     * By default there are no methods for the empty stub classes; override this
     * method if you want to change that behavior.
     *
     * @see ObjectBuilder::addClassBody()
     *
     * @param string $script
     *
     * @return void
     */
    protected function addClassBody(string &$script): void
    {
        $child = $this->getChild();
        $col = $child->getColumn();
        $cfc = $col->getPhpName();

        $const = 'CLASSKEY_' . $child->getConstantSuffix();

        $script .= "
    /**
     * Constructs a new " . $this->getChild()->getClassName() . ' class, setting the ' . $col->getName() . ' column to ' . $this->getTableMapClassName() . "::$const.
     */
    public function __construct()
    {";
        $script .= "
        parent::__construct();
        \$this->set$cfc(" . $this->getTableMapClassName() . '::CLASSKEY_' . $child->getConstantSuffix() . ");
    }
";
    }

    /**
     * Closes class.
     *
     * @param string $script The script will be modified in this method.
     *
     * @return void
     */
    protected function addClassClose(string &$script): void
    {
        $script .= "
}
";
    }
}
