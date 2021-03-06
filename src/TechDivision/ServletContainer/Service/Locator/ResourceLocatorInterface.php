<?php

/**
 * TechDivision\ServletContainer\Service\Locator\ResourceLocatorInterface
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\ServletContainer\Service\Locator;

use TechDivision\ServletContainer\Interfaces\Request;
use TechDivision\ServletContainer\Interfaces\Servlet;

/**
 * Interface for the resource locator instances.
 *
 * @package     TechDivision\ServletContainer
 * @copyright  	Copyright (c) 2010 <info@techdivision.com> - TechDivision GmbH
 * @license    	http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Markus Stockbauer <ms@techdivision.com>
 * @author      Tim Wagner <tw@techdivision.com>
 * @author      Johann Zelger <jz@techdivision.com>
 */
interface ResourceLocatorInterface {

    /**
     * Tries to locate the resource related with the request.
     *
     * @param Request $request The request instance to return the servlet for
     * @return TechDivision\ServletContainer\Interfaces\Servlet The requested servlet
     */
    public function locate(Request $request);
}
