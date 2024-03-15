<?php
/**
 * Class StreamClientNoCATest
 *
 * @created      23.02.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest;

use chillerlan\HTTPTest\ClientFactories\StreamClientNoCAFactory;
use PHPUnit\Framework\Attributes\Group;

/**
 *
 */
#[Group('slow')]
final class StreamClientNoCATest extends HTTPClientTestAbstract{

	protected string $HTTP_CLIENT_FACTORY = StreamClientNoCAFactory::class;

}
