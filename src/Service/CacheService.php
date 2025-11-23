<?php
namespace App\Service;

class CacheService
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?: sys_get_temp_dir() . '/app-cache';
        if (!is_dir($this->path)) {
            @mkdir($this->path, 0777, true);
        }
    }

    private function fileName(string $key): string
    {
        return $this->path . '/' . md5($key) . '.cache';
    }

    public function get(string $key, ?callable $fallback = null, int $ttlSeconds = 300)
    {
        $file = $this->fileName($key);
        if (file_exists($file)) {
            $data = json_decode((string)file_get_contents($file), true);
            if ($data && isset($data['expires_at']) && $data['expires_at'] >= time()) {
                return $data['value'];
            }
        }

        if ($fallback) {
            $value = $fallback();
            $this->set($key, $value, $ttlSeconds);
            return $value;
        }

        return null;
    }

    public function set(string $key, $value, int $ttlSeconds = 300): void
    {
        $file = $this->fileName($key);
        $payload = [
            'value' => $value,
            'expires_at' => time() + $ttlSeconds,
        ];
        @file_put_contents($file, json_encode($payload));
    }

    public function delete(string $key): void
    {
        $file = $this->fileName($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}
