<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Support;

use Closure;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Throwable;

final class BlockRegistryManifestStore
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly string $path,
        private readonly ?Closure $lockAcquirer = null,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function read(): ?array
    {
        if (! $this->filesystem->exists($this->path)) {
            return null;
        }

        try {
            $manifest = require $this->path;
        } catch (Throwable) {
            return null;
        }

        return is_array($manifest) ? $manifest : null;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    public function replaceAtomically(array $manifest): void
    {
        $directory = dirname($this->path);
        $this->filesystem->ensureDirectoryExists($directory);

        $lockPath = $this->path . '.lock';
        $temporaryPath = $this->path . '.' . bin2hex(random_bytes(8)) . '.tmp';
        $lockHandle = fopen($lockPath, 'c');

        if ($lockHandle === false) {
            throw new RuntimeException(sprintf('Unable to open content block manifest lock [%s].', $lockPath));
        }

        try {
            if (! $this->acquireLock($lockHandle)) {
                throw new RuntimeException(sprintf('Unable to lock content block manifest [%s].', $lockPath));
            }

            $this->filesystem->put($temporaryPath, '<?php return ' . var_export($manifest, true) . ';' . PHP_EOL);
            $this->filesystem->move($temporaryPath, $this->path);
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            $this->filesystem->delete($temporaryPath);
        }
    }

    public function forget(): void
    {
        $this->filesystem->delete($this->path);
        $temporaryPaths = $this->filesystem->glob($this->path . '.*.tmp');

        foreach (is_array($temporaryPaths) ? $temporaryPaths : [] as $temporaryPath) {
            $this->filesystem->delete($temporaryPath);
        }
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @param  resource  $lockHandle
     */
    private function acquireLock(mixed $lockHandle): bool
    {
        if ($this->lockAcquirer instanceof Closure) {
            return ($this->lockAcquirer)($lockHandle) === true;
        }

        return flock($lockHandle, LOCK_EX);
    }
}
