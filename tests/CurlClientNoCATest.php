<?php
/**
 * Class CurlClientNoCATest
 *
 * @created      28.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest;

use chillerlan\HTTPTest\ClientFactories\CurlClientNoCAFactory;
use PHPUnit\Framework\Attributes\Group;

/**
 *
 */
#[Group('slow')]
final class CurlClientNoCATest extends HTTPClientTestAbstract{

	protected string $HTTP_CLIENT_FACTORY = CurlClientNoCAFactory::class;

}
