<?php

/**
 * TechDivision\ServletContainer\GenericServlet
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\ServletContainer\Servlets;

use TechDivision\ServletContainer\Interfaces\Servlet;
use TechDivision\ServletContainer\Interfaces\ServletConfig;

/**
 * Abstract servlet implementation.
 *
 * @package     TechDivision\ServletContainer
 * @copyright  	Copyright (c) 2010 <info@techdivision.com> - TechDivision GmbH
 * @license    	http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Markus Stockbauer <ms@techdivision.com>
 * @author      Tim Wagner <tw@techdivision.com>
 */
abstract class GenericServlet implements Servlet {

    /**
     * The host configuration.
     * @var TechDivision\ServletContainer\ServletConfig
     */
    protected $config;

    /**
     * @param ServletConfig $config
     * @throws ServletException;
     * @return mixed
     */
    public function init(ServletConfig $config) {
        $this->config = $config;
    }

    /**
     * @return ServletConfig
     */
    public function getServletConfig() {
        return $this->config;
    }

    /**
     * @return mixed|void
     */
    public function getServletInfo() {
        return $this->getServletConfig()->getServerVars();
    }

    /**
     * @return mixed|void
     * @todo Implement destroy() method
     */
    public function destroy() {
    }
}