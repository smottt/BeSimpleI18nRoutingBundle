<?php

namespace BeSimple\I18nRoutingBundle\Routing\Loader;

use BeSimple\I18nRoutingBundle\Routing\I18nRoute;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Loader\YamlFileLoader as BaseYamlFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\FileLocatorInterface;

class YamlFileLoader extends BaseYamlFileLoader
{
	protected $currentLocale;

	public function __construct(FileLocatorInterface $locator, $currentLocale)
	{
		parent::__construct($locator);

		$this->currentLocale = $currentLocale;
	}

    private static $availableKeys = array(
        'locales', 'resource', 'type', 'prefix', 'pattern', 'path', 'host', 'schemes', 'methods', 'defaults', 'requirements', 'options',
    );

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean True if this class supports the given resource, false otherwise
     *
     * @api
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'be_simple_i18n' === $type && 'yml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     * {@inheritDoc}
     */
    protected function parseRoute(RouteCollection $collection, $name, array $config, $path)
    {
        $defaults     = isset($config['defaults']) ? $config['defaults'] : array();
        $requirements = isset($config['requirements']) ? $config['requirements'] : array();
        $options      = isset($config['options']) ? $config['options'] : array();
        $host         = isset($config['host']) ? $config['host'] : '';
        $schemes      = isset($config['schemes']) ? $config['schemes'] : array();
        $methods      = isset($config['methods']) ? $config['methods'] : array();

        $filteredLocales = array_intersect_key($config['locales'], array_flip(array($this->currentLocale)));

        if (empty($filteredLocales)) {
        	return;
        }

        $route = new I18nRoute($name, $filteredLocales, $defaults, $requirements, $options, $host, $schemes, $methods);
        $collection->addCollection($route->getCollection());
    }

    /**
     * {@inheritDoc}
     */
    protected function validate($config, $name, $path)
    {
        if (!is_array($config)) {
            throw new \InvalidArgumentException(sprintf('The definition of "%s" in "%s" must be a YAML array.', $name, $path));
        }

        $extraKeys = array_diff(array_keys($config), self::$availableKeys);

        if (!empty($extraKeys)) {
            throw new \InvalidArgumentException(sprintf(
                'The routing file "%s" contains unsupported keys for "%s": "%s". Expected one of: "%s".',
                $path, $name, implode('", "', $extraKeys), implode('", "', self::$availableKeys)
            ));
        }

        if (isset($config['resource']) && isset($config['locales'])) {
            throw new \InvalidArgumentException(sprintf(
                'The routing file "%s" must not specify both the "resource" key and the "locales" key for "%s". Choose between an import and a route definition.',
                $path, $name
            ));
        }

        if (!isset($config['resource']) && isset($config['type'])) {
            throw new \InvalidArgumentException(sprintf(
                'The "type" key for the route definition "%s" in "%s" is unsupported. It is only available for imports in combination with the "resource" key.',
                $name, $path
            ));
        }

        if (!isset($config['resource']) && !isset($config['locales'])) {
            throw new \InvalidArgumentException(sprintf(
                'You must define a "locales" for the route "%s" in file "%s".',
                $name, $path
            ));
        }
    }
}
