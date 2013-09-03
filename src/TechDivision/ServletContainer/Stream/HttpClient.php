<?php

/**
 * TechDivision\ServletContainer\Stream\HttpClient
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\ServletContainer\Stream;

use TechDivision\ServletContainer\Exceptions\InvalidHeaderException;
use TechDivision\ServletContainer\Http\HttpRequest;
use TechDivision\Stream\Client;

/**
 * The http client implementation that handles the request like a webserver
 *
 * @package     TechDivision\ServletContainer
 * @copyright  	Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license    	http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Johann Zelger <jz@techdivision.com>
 *              Philipp Dittert <p.dittert@techdivision.com>
 */
class HttpClient extends Client
{
    
    /**
     * The HttpRequest instance to use as factory.
     * @var \TechDivision\ServletContainer\Http\HttpRequest
     */
    protected $httpRequest;

    /**
     * The new line character.
     * @param $newLine
     */
    public function setNewLine($newLine) {
        $this->newLine = $newLine;
    }
    
    /**
     * Injects the HttpRequest instance to use as factory.
     * 
     * @param \TechDivision\ServletContainer\Http\HttpRequest $request The request instance to use
     * @return void
     */
    public function injectHttpRequest($request) {
        $this->httpRequest = $request;
    }
    
    /**
     * Returns the HttpRequest instance used as factory.
     * 
     * @return \TechDivision\ServletContainer\Http\HttpRequest The request instance
     */
    public function getHttpRequest() {
        return $this->httpRequest;
    }

    /**
     * Receive a Stream from Socket an check it is valid
     *
     * @return mixed
     * @throws InvalidHeaderException Is thrown if the header is complete but not valid
     */
    public function receive()
    {
        
        // initialize the buffer
        $buffer = null;
        
        // read a chunk from the socket
        while ($buffer .= $this->read($this->getLineLength())) {
            // check if header finished
            if (false !== strpos($buffer, $this->getNewLine())) {
                break;
            }
        }

        // separate header from body chunk
        list ($rawHeader) = explode($this->getNewLine(), $buffer);

        $body = str_replace($rawHeader . $this->getNewLine(), '', $buffer);

        // get method type instance inited by raw headers
        $requestInstance = $this->getHttpRequest()->initFromRawHeader($rawHeader);

        // check if body-length not reached content-length already
        if (($contentLength = $requestInstance->getHeader('Content-Length'))
            && ($contentLength > strlen($body)))
        {
            // read a chunk from the socket till content length is reached
            while ($line = $this->read($this->getLineLength())) {
                // append body
                $body .= $line;

                // if length is reached break here
                if (strlen($body) == (int)$contentLength) {
                    break;
                }
            }
        }

        // parse body with request instance
        $requestInstance->parse($body);

        // return fully qualified request instance
        return $requestInstance;
    }
}