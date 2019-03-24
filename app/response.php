<?php
declare (strict_types = 1);

namespace App;

/**
 * Response.
 *
 * @author    Mohamed LAMGOUNI <focus3d.ro@gmail.com>
 * @since    v0.0.1
 * @version    v1.0.0    Monday, March 18th, 2019.
 * @global
 */
class Response
{

    /**
     * Status & Content to output as JSON
     * @var array
     */
    private $status;
    private $content;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setStatus(200);
        $this->setContent('');
    }

    /**
     * Display the response
     * @return void
     */
    public function finish()
    {
        // build JSON string to return

        $json = json_encode(
            array(
                'status' => $this->status,
                'content' => $this->content,
            )
        );

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
        header('Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods, Authorization, Accept, Origin, X-Requested-With');
        header("Access-Control-Max-Age: 600");
        header("Content-Type: application/json");
        echo $json;
        exit();
    }

    /**
     * Sets the statusObject
     * @var String $status
     * @var String $statusText
     */
    final public function setStatus($statusCode)
    {
        // Long list. Just remember these though: 200, 201, 204, (301, 302,) 400, 401, 403, 404, 500, 501
        $codes = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Reserved)',
            307 => 'Temporary Redirect',
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
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
        );
        $statusCode = (int) (in_array($statusCode, array_keys($codes)) ? $statusCode : 500);
        $this->status = array(
            'code' => $statusCode,
            'info' => $codes[$statusCode],
        );
        header('HTTP/1.1 ' . $this->status['code'] . ' ' . $this->status['info']);
    }



    /**
     * Sets the data to output
     * @var array
     */
    final public function setContent($content)
    {
        $this->content = $content;
    }
}
