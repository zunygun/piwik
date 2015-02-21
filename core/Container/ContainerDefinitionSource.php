<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Container;

use DI\Container;
use DI\Definition\Definition;
use DI\Definition\Exception\DefinitionException;
use DI\Definition\Source\ChainableDefinitionSource;
use DI\Definition\ValueDefinition;
use DI\NotFoundException;

/**
 * TODO
 */
class ContainerDefinitionSource extends ChainableDefinitionSource
{
    /**
     * TODO
     *
     * @var Container
     */
    private $container;

    /**
     * TODO
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $name
     * @return Definition|null
     */
    protected function findDefinition($name)
    {
        try {
            $value = $this->container->get($name);
            return new ValueDefinition($name, $value);
        } catch (NotFoundException $ex) {
            return null;
        } catch (DefinitionException $ex) {
            return null;
        }
    }
}