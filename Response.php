<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 * @since 1.0
 */

namespace Garden;

use JsonSerializable;

/**
 * A class that contains the information in an http response.
 */
class Response implements JsonSerializable {
    /// Properties ///

    /**
     * An array of cookie sets. This array is in the form:
     *
     * ```
     * array (
     *     'name' => [args for setcookie()]
     * )
     * ```
     *
     * @var array An array of cookies sets.
     */
    protected $cookies = [];

    /**
     * @var Response The current response.
     */
    protected static $current;

    /**
     * @var array An array of meta data that is not related to the response data.
     */
    protected $meta = [];

    /**
     * @var array An array of response data.
     */
    protected $data = [];

    /**
     * @var string The default cookie domain.
     */
    public $defaultCookieDomain;

    /**
     * @var string The default cookie path.
     */
    public $defaultCookiePath;

    /**
     * @var array An array of http headers.
     */
    protected $headers = [];

    /**
     * @var int HTTP status code
     */
    protected $status = 200;

    /**
     * @var string body of response
     */
    protected $body = '';

    /**
     * @var array
     */
    protected static $specialHeaders = [
        'etag' => 'ETag',
        'p3p' => 'P3P',
        'www-authenticate' => 'WWW-Authenticate',
        'x-ua-compatible' => 'X-UA-Compatible'
    ];

    /**
     * @var array HTTP response codes and messages.
     */
    protected static $messages = [
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        // Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    ];

    /// Methods ///

    /**
     * Gets or sets the response that is currently being processed.
     *
     * @param Response|null $response Set a new response or pass null to get the current response.
     * @return Response Returns the current response.
     */
    public static function current(Response $response = null): Response
    {
        if ($response !== null) {
            self::$current = $response;
        } elseif (self::$current === null) {
            self::$current = new self();
        }

        return self::$current;
    }

    /**
     * Create a Response from a variety of data.
     *
     * @param mixed $result The result to create the response from.
     * @return Response Returns a {@link Response} object.
     */
    public static function create($result): self
    {
        if ($result instanceof self) {
            return $result;
        }

        if ($result instanceof Exception\Response) {
            return $result->getResponse();
        }

        $response = new self();

        if ($result instanceof Exception\Client) {
            $response->setStatus($result->getCode());
            $response->setHeaders($result->getHeaders());
            $response->setData($result->jsonSerialize());
        } elseif ($result instanceof \Exception) {
            $response->setStatus($result->getCode());
            $response->setData([
                'exception' => $result->getMessage(),
                'code' => $result->getCode()
            ]);
        } else {
            $response->setStatus(422);
            $response->setData([
                'exception' => 'Unknown result type for response.',
                'code' => $response->status()
            ]);
        }

        return $response;
    }

    /**
     * Sets a body of response
     *
     * @param string $body
     */
    public function setBody(string $body)
    {
        $this->body = $body;
    }

    /**
     * Gets a body of response
     *
     * @return string
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * Get the current content type
     *
     * @return string
     */
    public function contentType(): string
    {
        return $this->header('Content-Type');
    }

    /**
     * Set the content type.
     *
     * @param string $value The new content type
     */
    public function setContentType(string $value)
    {
        $this->setHeader('Content-Type', $value);
    }

    /**
     * Set the content type from an accept header.
     *
     * @param string $accept The value of the accept header.
     * @return bool
     */
    public function setContentTypeFromAccept(string $accept): bool
    {
        if (!empty($this->headers['Content-Type'])) {
            return false;
        }

        $accept = strtolower($accept);
        if (strpos($accept, ',') === false) {
            list($contentType) = explode(';', $accept);
        } elseif (strpos($accept, 'text/html') !== false) {
            $contentType = 'text/html';
        } elseif (strpos($accept, 'application/rss+xml') !== false) {
            $contentType = 'application/rss+xml';
        } elseif (strpos($accept, 'text/plain')) {
            $contentType = 'text/plain';
        } else {
            $contentType = 'text/html';
        }
        $this->setContentType($contentType);

        return true;
    }

    /**
     * Translate an http code to its corresponding status message.
     *
     * @param int $statusCode The http status code.
     * @param bool $header Whether or not the result should be in a form that can be passed to {@link header}.
     * @return string Returns the status message corresponding to {@link $code}.
     */
    public static function statusMessage($statusCode, $header = false): string
    {
        $message = val($statusCode, self::$messages, 'Unknown');

        if ($header) {
            return "HTTP/1.1 $statusCode $message";
        }

        return $message;
    }

    /**
     * Set cookie value
     *
     * @param string $name
     * @param string $value
     * @param int $lifetime
     * @param array $options ['path' => ..., 'domain' => ..., 'secure' => ..., 'httponly' => ...]
     */
    public function cookie($name, $value, $lifetime, array $options = [])
    {
        $this->cookies[$name] = [
            $value,
            $lifetime > 0 ? time() + $lifetime : 0,
            val('path', $options, $this->defaultCookiePath),
            val('domain', $options, $this->defaultCookieDomain),
            val('secure', $options),
            val('httponly', $options)
        ];
    }

    /**
     * Delete cookie
     *
     * @param string $name
     */
    public function deleteCookie(string $name)
    {
        $this->cookie($name, null, -1);
    }

    /**
     * Get the meta data for the response.
     * The meta is an array of data that is unrelated to the response data.
     *
     * @return array Returns either the meta
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * Set the meta data for the response.
     * The meta is an array of data that is unrelated to the response data.
     *
     * @param array $meta Pass a new meta data value
     * @param bool $merge Whether or not to merge new data with the current data when setting.
     */
    public function setMeta(array $meta, $merge = false)
    {
        $this->meta = $merge ? array_merge($this->meta, $meta) : $meta;
    }

    /**
     * Get the data for the response.
     *
     * @return Response|array Returns either the data or `$this` when setting the data.
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Set the data for the response.
     *
     * @param array $data Pass a new data value
     * @param bool $merge Whether or not to merge new data with the current data when setting.
     */
    public function setData(array $data, $merge = false)
    {
        $this->data = $merge ? array_merge($this->data, $data) : $data;
    }

    /**
     * Gets headers.
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get header.
     *
     * @param string $name The name of the header or an array of headers.
     * @return string
     */
    public function header($name): string
    {
        return val($name, $this->headers, null);
    }

    /**
     * Set header
     *
     * @param string $name The name of the header or an array of headers.
     * @param string $value A new value for the header or null to get the current header.
     */
    public function setHeader($name, $value = null)
    {
        if (strpos($name, ':') !== false) {
            // The name is in the form Header: value.
            list($name, $value) = explode(':', $name, 2);
        }

        $this->headers[static::normalizeHeader($name)] = trim($value);
    }

    /**
     * Sets headers.
     *
     * @param array $headers array of headers
     * @param bool $replace Whether or not to replace the current header or append.
     */
    public function setHeaders(array $headers, $replace = true)
    {
        foreach ($headers as $name => $value) {
            if (is_numeric($name)) {
                // $value should be a header in the form Header: value.
                list($name, $value) = explode(':', $value, 2);
            }

            $name = static::normalizeHeader($name);

            if ($replace || !isset($this->headers[$name])) {
                $this->headers[$name] = trim($value);
            }
        }
    }

    /**
     * Normalize a header key to the proper casing.
     *
     * Example:
     *
     * ```
     * echo static::normalizeHeader('CONTENT_TYPE');
     *
     * // Content-Type
     * ```
     *
     * @param string $name The name of the header.
     * @return string Returns the normalized header name.
     */
    public static function normalizeHeader(string $name): string
    {
        $name = str_replace(['-', '_'], ' ', strtolower($name));

        if (isset(self::$specialHeaders[$name])) {
            return self::$specialHeaders[$name];
        }

        return str_replace(' ', '-', ucwords($name));
    }

    /**
     * Gets/sets the http status code.
     *
     * @return int The current http status code.
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Sets the http status code.
     *
     * @param int $value The new value if setting the http status code.
     * @return int current status
     */
    public function setStatus(int $value): int
    {
        if (!isset(self::$messages[$value])) {
            $this->setHeader('X-Original-Status', $value);
            $value = 500;
        }

        $this->status = $value;

        return $this->status;
    }

    /**
     * Flush the response to the client.
     */
    public function flush()
    {
        $this->flushHeaders();

        echo json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Flush the headers to the browser.
     *
     */
    public function flushHeaders()
    {
        if (headers_sent()) {
            return;
        }

        // Set the cookies first.
        foreach ($this->cookies as $name => $params) {
            setcookie($name, ...$params);
        }

        // Set the response code.
        header(static::statusMessage($this->status, true), true, $this->status);

        $headers = array_filter($this->headers);

        // The content type is a special case.
        if (isset($headers['Content-Type'])) {
            $contentType = (array)$headers['Content-Type'];
            header('Content-Type: ' . reset($contentType) . '; charset=utf-8');
            unset($headers['Content-Type']);
        }

        // Flush the rest of the headers.
        foreach ($headers as $name => $value) {
            foreach ((array)$value as $hvalue) {
                header("$name: $hvalue", false);
            }
        }
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * Redirect
     * @param $url
     */
    public function redirect($url)
    {
        $request = Request::current();
        $host = Request::current()->getHost();
        $scheme = $request->getScheme();

        $url = Helpers\Validate::url($url) ? $url : $scheme . '://' . $host . $url;

        header("Location: $url");

        exit;
    }
}
