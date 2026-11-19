<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Readiness check: 200 when the database, cache and message queue respond, 503 otherwise.
 */
final class HealthController
{
    public function __construct(
        private readonly Connection $connection,
        #[Autowire(service: 'cache.app')]
        private readonly CacheItemPoolInterface $cache,
        #[Autowire(service: 'messenger.transport.async')]
        private readonly TransportInterface $queue,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check('database', fn () => $this->connection->executeQuery('SELECT 1')),
            'cache' => $this->check('cache', function (): void {
                $item = $this->cache->getItem('health_'.bin2hex(random_bytes(4)))->set(true);
                if (!$this->cache->save($item) || true !== $this->cache->getItem($item->getKey())->get()) {
                    throw new \RuntimeException('cache read-back failed');
                }
                $this->cache->deleteItem($item->getKey());
            }),
            'queue' => $this->check('queue', function (): void {
                if ($this->queue instanceof MessageCountAwareInterface) {
                    $this->queue->getMessageCount();
                }
            }),
        ];

        $healthy = !\in_array('fail', $checks, true);

        return new JsonResponse(
            ['status' => $healthy ? 'ok' : 'fail', 'checks' => $checks],
            $healthy ? 200 : 503,
        );
    }

    private function check(string $name, callable $probe): string
    {
        try {
            $probe();

            return 'ok';
        } catch (\Throwable $e) {
            $this->logger->error('Health check failed: {check}', ['check' => $name, 'exception' => $e]);

            return 'fail';
        }
    }
}
