<?php

namespace Laminas\OAuth\Token;

use Laminas\Http\Response as HTTPResponse;
use Laminas\OAuth\Http\Utility as HTTPUtility;

/**
 * @category   Laminas
 * @package    Laminas_OAuth
 */
abstract class AbstractToken implements TokenInterface
{
    /**@+
     * Token constants
     */
    const TOKEN_PARAM_KEY                = 'oauth_token';
    const TOKEN_SECRET_PARAM_KEY         = 'oauth_token_secret';
    const TOKEN_PARAM_CALLBACK_CONFIRMED = 'oauth_callback_confirmed';
    /**@-*/

    /**
     * Token parameters
     *
     * @var array
     */
    protected $params = [];

    /**
     * OAuth response object
     *
     * @var \Laminas\Http\Response
     */
    protected $response = null;

    /**
     * @var \Laminas\OAuth\Http\Utility
     */
    protected $httpUtility = null;

    /**
     * Constructor; basic setup for any Token subclass.
     *
     * @param  null|\Laminas\Http\Response $response
     * @param  null|\Laminas\OAuth\Http\Utility $utility
     * @return void
     */
    public function __construct(
        HTTPResponse $response = null,
        HTTPUtility $utility = null
    ) {
        if ($response !== null) {
            $this->response = $response;
            $params = $this->parseParameters($response);
            if (count($params) > 0) {
                $this->setParams($params);
            }
        }
        if ($utility !== null) {
            $this->httpUtility = $utility;
        } else {
            $this->httpUtility = new HTTPUtility;
        }
    }

    /**
     * Attempts to validate the Token parsed from the HTTP response - really
     * it's just very basic existence checks which are minimal.
     *
     * @return bool
     */
    public function isValid()
    {
        if (isset($this->params[self::TOKEN_PARAM_KEY])
            && ! empty($this->params[self::TOKEN_PARAM_KEY])
            && isset($this->params[self::TOKEN_SECRET_PARAM_KEY])
        ) {
            return true;
        }
        return false;
    }

    /**
     * Return the HTTP response object used to initialise this instance.
     *
     * @return \Laminas\Http\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets the value for the this Token's secret which may be used when signing
     * requests with this Token.
     *
     * @param  string $secret
     * @return \Laminas\OAuth\Token\AbstractToken
     */
    public function setTokenSecret($secret)
    {
        $this->setParam(self::TOKEN_SECRET_PARAM_KEY, $secret);
        return $this;
    }

    /**
     * Retrieve this Token's secret which may be used when signing
     * requests with this Token.
     *
     * @return string
     */
    public function getTokenSecret()
    {
        return $this->getParam(self::TOKEN_SECRET_PARAM_KEY);
    }

    /**
     * Sets the value for a parameter (e.g. token secret or other) and run
     * a simple filter to remove any trailing newlines.
     *
     * @param  string $key
     * @param  string $value
     * @return \Laminas\OAuth\Token\AbstractToken
     */
    public function setParam($key, $value)
    {
        $this->params[$key] = trim($value, "\n");
        return $this;
    }

    /**
     * Sets the value for some parameters (e.g. token secret or other) and run
     * a simple filter to remove any trailing newlines.
     *
     * @param  array $params
     * @return \Laminas\OAuth\Token\AbstractToken
     */
    public function setParams(array $params)
    {
        foreach ($params as $key => $value) {
            $this->setParam($key, $value);
        }
        return $this;
    }

    /**
     * Get the value for a parameter (e.g. token secret or other).
     *
     * @param  string $key
     * @return mixed
     */
    public function getParam($key)
    {
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }
        return null;
    }

    /**
     * Sets the value for a Token.
     *
     * @param  string $token
     * @return \Laminas\OAuth\Token\AbstractToken
     */
    public function setToken($token)
    {
        $this->setParam(self::TOKEN_PARAM_KEY, $token);
        return $this;
    }

    /**
     * Gets the value for a Token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->getParam(self::TOKEN_PARAM_KEY);
    }

    /**
     * Generic accessor to enable access as public properties.
     *
     * @return string
     */
    public function __get($key)
    {
        return $this->getParam($key);
    }

    /**
     * Generic mutator to enable access as public properties.
     *
     * @param  string $key
     * @param  string $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setParam($key, $value);
    }

    /**
     * Convert Token to a string, specifically a raw encoded query string.
     *
     * @return string
     */
    public function toString()
    {
        return $this->httpUtility->toEncodedQueryString($this->params);
    }

    /**
     * Convert Token to a string, specifically a raw encoded query string.
     * Aliases to self::toString()
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Parse a HTTP response body and collect returned parameters
     * as raw url decoded key-value pairs in an associative array.
     *
     * @param  \Laminas\Http\Response $response
     * @return array
     */
    protected function parseParameters(HTTPResponse $response)
    {
        $params = [];
        $body   = $response->getBody();
        if (empty($body)) {
            return [];
        }

        // validate body based on acceptable characters...todo
        $parts = explode('&', $body);
        foreach ($parts as $kvpair) {
            $pair = explode('=', $kvpair);
            $params[rawurldecode($pair[0])] = rawurldecode($pair[1]);
        }
        return $params;
    }

    /**
     * Limit serialisation stored data to the parameters
     */
    public function __sleep()
    {
        return ['params'];
    }

    /**
     * After serialisation, re-instantiate a HTTP utility class for use
     */
    public function __wakeup()
    {
        if ($this->httpUtility === null) {
            $this->httpUtility = new HTTPUtility;
        }
    }
}
