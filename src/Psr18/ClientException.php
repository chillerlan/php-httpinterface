<?php
/**
 * Class ClientException
 *
 * @filesource   ClientException.php
 * @created      10.09.2018
 * @package      chillerlan\HTTP\Psr18
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr18;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;

class ClientException extends Exception implements ClientExceptionInterface{}
