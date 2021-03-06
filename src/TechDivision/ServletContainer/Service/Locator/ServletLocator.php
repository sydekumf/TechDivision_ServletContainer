<?php

/**
 * TechDivision\ServletContainer\Service\Locator\ServletLocator
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\ServletContainer\Service\Locator;

use TechDivision\ServletContainer\Service\Locator\ResourceLocatorInterface;
use TechDivision\ServletContainer\Interfaces\Request;
use TechDivision\ServletContainer\Interfaces\Servlet;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use TechDivision\ServletContainer\Exceptions\ServletNotFoundException;

/**
 * The servlet resource locator implementation.
 *
 * @package TechDivision\ServletContainer
 * @copyright Copyright (c) 2010 <info@techdivision.com> - TechDivision GmbH
 * @license http://opensource.org/licenses/osl-3.0.php
 *          Open Software License (OSL 3.0)
 * @author Markus Stockbauer <ms@techdivision.com>
 * @author Tim Wagner <tw@techdivision.com>
 */
class ServletLocator implements ResourceLocatorInterface
{

    /**
     * The servlet manager instance.
     *
     * @var \TechDivision\ServletContainer\ServletManager
     */
    protected $servletManager;

    /**
     * The collection with the initialized routes.
     *
     * @var \Symfony\Component\Routing\RouteCollection
     */
    protected $routes;

    protected $securedUrlRoutes;

    /**
     * Initializes the locator with the actual servlet manager instance.
     *
     * @param \TechDivision\ServletContainer\ServletManager $servletManager
     *            The servlet manager instance
     * @return void
     */
    public function __construct($servletManager)
    {
        $this->servletManager = $servletManager;
        $this->initRoutes();
        $this->initSecuredUrlRoutes();

    }

    /**
     * Returns the servlet manager instance to use.
     *
     * @return \TechDivision\ServletContainer\ServletManager The servlet manager instance to use
     */
    public function getServletManager()
    {
        return $this->servletManager;
    }

    /**
     * Returns the actual application instance.
     *
     * @return \TechDivision\ServletContainer\Application The application instance
     */
    public function getApplication()
    {
        return $this->getServletManager()->getApplication();
    }

    /**
     * Prepares a collection with routes generated from the available servlets
     * ans their servlet mappings.
     *
     * @return \Symfony\Component\Routing\RouteCollection The collection with the available routes
     */
    public function initRoutes()
    {
        
        // retrieve the registered servlets
        $servletMappings = $this->getServletManager()->getServletMappings();
        $servlets = $this->getServletManager()->getServlets();
        
        // prepare the collection with the available routes and initialize the route counter
        $this->routes = new RouteCollection();
        $counter = 0;
        
        // iterate over the available servlets and prepare the routes
        foreach ($servletMappings as $urlPattern => $servletName) {
            $servlet = $servlets[$servletName];
            $pattern = str_replace('/*', "/{placeholder_$counter}", $urlPattern);
            $route = new Route($pattern, array(
                $servlet
            ), array(
                "{placeholder_$counter}" => '.*'
            ));
            $this->routes->add($counter ++, $route);
        }
    }

    /**
     * Prepares a collection with routes generated from the available servlets
     * ans their servlet mappings.
     *
     * @return \Symfony\Component\Routing\RouteCollection The collection with the available routes
     */
    public function initSecuredUrlRoutes()
    {
        // retrieve the registered servlets
        $securedUrlConfigs = $this->getServletManager()->getSecuredUrlConfigs();

        // prepare the collection with the available routes and initialize the route counter
        $routes = new RouteCollection();
        $counter = 0;

        // iterate over the available servlets and prepare the routes
        foreach ($securedUrlConfigs as $config) {
            $urlPattern = $config['url-pattern'];
            $pattern = str_replace('/*', "/{placeholder_$counter}", $urlPattern);
            $route = new Route($pattern, array(
                $config['auth']
            ), array(
                "{placeholder_$counter}" => '.*'
            ));
            $routes->add($counter ++, $route);
        }
        $this->securedUrlRoutes = $routes;
    }

    /**
     * Returns the collection with the initialized routes.
     *
     * @return \Symfony\Component\Routing\RouteCollection The initialize routes
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    public function getSecuredUrlRoutes()
    {
        return $this->securedUrlRoutes;
    }

    /**
     * Tries to locate a servlet for the passed request instance.
     *
     * @param Request $request
     *            The request instance to return the servlet for
     * @return TechDivision\ServletContainer\Interfaces\Servlet The requested servlet
     * @throws \TechDivision\ServletContainer\Exceptions\ServletNotFoundException Is thrown if no servlet can be found for the passed request
     * @see \TechDivision\ServletContainer\Service\Locator\ResourceLocatorInterface::locate()
     */
    public function locate(Request $request)
    {
        // build the file-path of the request
        $path = $request->getPathInfo();
        
        // check if the application is loaded by a VHost
        $applicationName = $this->getApplication()->getName();
        if (! $this->getApplication()->isVhostOf($request->getServerName())) {
            $path = '/' . ltrim(str_replace("/{$applicationName}", "/", $path), '/');
        }

        // load the servlet cache and check if a servlet has already been loaded
        $servletCache = $this->getApplication()
            ->getInitialContext()
            ->getAttribute("$applicationName.servletCache");

        // load the route collection, initialize the context for the routing and the URL matcher
        $context = new RequestContext($path, $request->getMethod(), $request->getServerName());

        if (is_array($servletCache) && array_key_exists($path, $servletCache)) {
            $servlet = $this->servletManager->getServlet($servletCache[$path]);
            // check if current Path should be secured by HTTP Authentication
            $this->secureUrlMatcher($context, $path, $servlet);
            return $servlet;
        } elseif (! is_array($servletCache)) {
            $servletCache = array();
        }
        
        // print_r($this->getRoutes());
        $matcher = new UrlMatcher($this->getRoutes(), $context);
        
        // traverse the path to find matching servlet
        do {
            
            try {
                $servlet = $matcher->match($path);
                break;
            } catch (ResourceNotFoundException $rnfe) {
                $path = substr($path, 0, strrpos($path, '/'));
            }
        } while (strpos($path, '/') !== FALSE);
        
        // check at least one servlet has been found
        if (is_array($servlet) === false || sizeof($servlet) === 0) {
            throw new ServletNotFoundException("Can't find servlet for requested path $path");
        }
        // load the the servlet instance from the matching result
        $mappingServlet = current($servlet);

        // check if current Path should be secured by HTTP Authentication
        $this->secureUrlMatcher($context, $path, $mappingServlet);

        // append it to the servlet cache and return the servlet
        $servletCache[$path] = $mappingServlet->getServletConfig()->getServletName();
        $this->getApplication()
            ->getInitialContext()
            ->setAttribute("$applicationName.servletCache", $servletCache);
        return $mappingServlet;
    }


    /**
     * search for a matching url pattern
     *
     * @param $context
     * @param $path
     * @return array
     */
    protected function secureUrlMatcher($context, $path, $servlet)
    {
        $matcher = new UrlMatcher($this->getSecuredUrlRoutes(), $context);
        // traverse the path to find matching servlet
        do {

            try {
                $config = $matcher->match($path);

                // url has matched
                $servlet->injectSecuredUrlConfig($config[0]);
                $servlet->setAuthenticationRequired(true);
                break;
            } catch (ResourceNotFoundException $rnfe) {
                $path = substr($path, 0, strrpos($path, '/'));
            }
        } while (strpos($path, '/') !== FALSE);

        return;
    }
}
