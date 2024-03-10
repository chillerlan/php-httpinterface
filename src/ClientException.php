<?php
/**
 * Class ClientException
 *
 * @created      10.09.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTP;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;

/**
 *
 */
class ClientException extends Exception implements ClientExceptionInterface{

}
