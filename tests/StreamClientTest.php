<?php
/**
 * Class StreamClientTest
 *
 * @created      23.02.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest;

use chillerlan\HTTPTest\ClientFactories\StreamClientFactory;
use PHPUnit\Framework\Attributes\Group;

/**
 *
 */
#[Group('slow')]
final class StreamClientTest extends HTTPClientTestAbstract{

	protected string $HTTP_CLIENT_FACTORY = StreamClientFactory::class;

}
