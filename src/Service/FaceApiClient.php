<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Log\LoggerInterface;

class FaceApiClient
{
    private HttpClientInterface $client;
    private array $options;
    private ?LoggerInterface $faceLogger;

    public function __construct(HttpClientInterface $client, array $options = [], ?LoggerInterface $faceLogger = null)
    {
        $this->client = $client;
        $this->options = $options;
        $this->faceLogger = $faceLogger;
    }

    /**
     * Enroll a face by saving the file locally and optionally returning provider token.
     * For Face++ we don't create persistent face tokens here; we'll keep the saved image and use compare.
     */
    /**
     * Enroll accepts either an UploadedFile or a base64 string (data URL or raw base64).
     * Returns a base64 string suitable for storing in DB.
     */
    public function enroll($fileOrBase64, string $targetDir): string
    {
        if ($fileOrBase64 instanceof UploadedFile) {
            $contents = file_get_contents($fileOrBase64->getPathname());
            return base64_encode($contents);
        }

        if (is_string($fileOrBase64)) {
            $data = $fileOrBase64;
            if (str_starts_with($data, 'data:')) {
                $parts = explode(',', $data, 2);
                $data = $parts[1] ?? '';
            }
            // Validate base64
            $decoded = base64_decode($data, true);
            if ($decoded === false) {
                throw new \InvalidArgumentException('Invalid base64 data provided to enroll()');
            }
            return $data;
        }

        throw new \InvalidArgumentException('enroll expects UploadedFile or base64 string');
    }

    /**
     * Verify two images using configured provider. Returns ['success' => bool, 'score' => float|null, 'raw' => array]
     *
     * $probe can be either an UploadedFile or a base64 string (data URL or raw base64).
     * $enrolledFilePath may be a filesystem path or a base64 string for the enrolled image.
     */
    public function verify($probe, string $enrolledFilePath): array
    {
        $provider = $this->options['provider'] ?? 'faceplusplus';
        $this->faceLogger?->debug('Face verify started.', ['provider' => $provider]);

        if ($provider === 'faceplusplus') {
            $apiKey = $this->options['api_key'] ?? null;
            $apiSecret = $this->options['api_secret'] ?? null;
            $url = $this->options['url'] ?? 'https://api-us.faceplusplus.com/facepp/v3/compare';

            if (!$apiKey || !$apiSecret) {
                $this->faceLogger?->error('Face verify failed: missing API credentials.');
                return ['success' => false, 'score' => null, 'raw' => ['error' => 'Missing API credentials']];
            }

            // Normalize probe and enrolled into base64 strings when needed
            $probeTemp = null;
            $probePath = null;
            $probeBase64 = null;

            if ($probe instanceof UploadedFile) {
                $probePath = $probe->getPathname();
            } elseif (is_string($probe)) {
                $data = $probe;
                if (str_starts_with($data, 'data:')) {
                    $parts = explode(',', $data, 2);
                    $data = $parts[1] ?? '';
                }
                // If it's raw base64, keep it
                $decoded = base64_decode($data, true);
                if ($decoded === false) {
                    $this->faceLogger?->warning('Face verify failed: invalid probe base64 payload.');
                    return ['success' => false, 'score' => null, 'raw' => ['error' => 'Invalid probe base64 data']];
                }
                $probeBase64 = $data;
                // We'll create a temp file later if we need a file path
                $probeTemp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'probe_' . bin2hex(random_bytes(8)) . '.jpg';
                file_put_contents($probeTemp, $decoded);
                $probePath = $probeTemp;
            } else {
                $this->faceLogger?->warning('Face verify failed: invalid probe type.', ['type' => get_debug_type($probe)]);
                return ['success' => false, 'score' => null, 'raw' => ['error' => 'Invalid probe type']];
            }

            // enrolledFilePath may be a filesystem path or a base64-encoded image
            $tempEnrolled = null;
            $enrolledPath = null;
            $enrolledBase64 = null;
            if (is_file($enrolledFilePath)) {
                $enrolledPath = $enrolledFilePath;
            } else {
                $data = $enrolledFilePath;
                if (str_starts_with($data, 'data:')) {
                    $parts = explode(',', $data, 2);
                    $data = $parts[1] ?? '';
                }
                $bytes = base64_decode($data);
                if ($bytes === false) {
                    if (isset($probeTemp) && is_file($probeTemp)) {
                        @unlink($probeTemp);
                    }
                    $this->faceLogger?->warning('Face verify failed: invalid enrolled base64 payload.');
                    return ['success' => false, 'score' => null, 'raw' => ['error' => 'Invalid enrolled base64 data']];
                }
                $tempEnrolled = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'enrolled_' . bin2hex(random_bytes(8)) . '.jpg';
                file_put_contents($tempEnrolled, $bytes);
                $enrolledPath = $tempEnrolled;
                $enrolledBase64 = $data; // raw base64 string
            }

            // If the PHP fileinfo extension is not available, avoid using file streams (which trigger MIME guessers)
            $fileinfoAvailable = extension_loaded('fileinfo') || function_exists('finfo_open');
            // Allow configuration to prefer base64 transport (avoids file streams entirely). Default: true for portability in dev.
            $preferBase64 = $this->options['prefer_base64'] ?? true;

            try {
                // Use base64 transport when fileinfo is unavailable or when prefer_base64 is enabled
                if ($preferBase64 || !$fileinfoAvailable) {
                    // Use base64 fields supported by Face++: image_base64_1 and image_base64_2
                    // Ensure we have base64 strings for both images
                    if ($enrolledBase64 === null) {
                        // enrolledPath is a file path: read and base64 encode
                        $bytes = file_get_contents($enrolledPath);
                        $enrolledBase64 = base64_encode($bytes);
                    }
                    if ($probeBase64 === null && $probePath && is_file($probePath)) {
                        $bytes = file_get_contents($probePath);
                        $probeBase64 = base64_encode($bytes);
                    }

                    $response = $this->client->request('POST', $url, [
                        'body' => [
                            'api_key' => $apiKey,
                            'api_secret' => $apiSecret,
                            'image_base64_1' => $enrolledBase64,
                            'image_base64_2' => $probeBase64,
                        ],
                    ]);
                } else {
                    // Default behavior: send as multipart files
                    $response = $this->client->request('POST', $url, [
                        'body' => [
                            'api_key' => $apiKey,
                            'api_secret' => $apiSecret,
                            'image_file1' => fopen($enrolledPath, 'r'),
                            'image_file2' => fopen($probePath, 'r'),
                        ],
                    ]);
                }

                $status = $response->getStatusCode();
                $content = $response->getContent(false);
                $data = json_decode($content, true);
                $this->faceLogger?->debug('Face verify provider response received.', ['status' => $status]);

                // cleanup temp files if we created one
                if (isset($tempEnrolled) && is_file($tempEnrolled)) {
                    @unlink($tempEnrolled);
                }
                if (isset($probeTemp) && is_file($probeTemp)) {
                    @unlink($probeTemp);
                }

                if ($status >= 200 && $status < 300 && isset($data['confidence'])) {
                    // Face++ returns confidence: higher is more similar
                    $score = (float) $data['confidence'];
                    $this->faceLogger?->info('Face verify successful.', ['score' => $score]);
                    // Threshold can be adjusted by caller
                    return ['success' => true, 'score' => $score, 'raw' => $data];
                }

                $this->faceLogger?->warning('Face verify failed: unexpected provider response.', [
                    'status' => $status,
                    'hasErrorMessage' => isset($data['error_message']),
                ]);

                return ['success' => false, 'score' => null, 'raw' => $data ?: ['status' => $status, 'content' => $content]];
            } catch (\Throwable $e) {
                // cleanup temp files on exception
                if (isset($tempEnrolled) && is_file($tempEnrolled)) {
                    @unlink($tempEnrolled);
                }
                if (isset($probeTemp) && is_file($probeTemp)) {
                    @unlink($probeTemp);
                }

                $this->faceLogger?->error('Face verify exception.', ['message' => $e->getMessage()]);
                return ['success' => false, 'score' => null, 'raw' => ['error' => $e->getMessage()]];
            }
        }

        // Default: no provider
        $this->faceLogger?->error('Face verify failed: no provider configured.');
        return ['success' => false, 'score' => null, 'raw' => ['error' => 'No provider configured']];
    }
}
