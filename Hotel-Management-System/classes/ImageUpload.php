<?php

//ImageUpload Class - Handles file uploads for room images

class ImageUpload
{
    private string $targetFolder;
    private array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private int $maxFileSize = 5242880; // 5MB
    private ?string $lastError = null;

    public function __construct(string $targetFolder = 'assets/')
    {
        $this->targetFolder = rtrim($targetFolder, '/') . '/';
    }

    /**
     * Set allowed MIME types
     * 
     * @param array $types Array of allowed MIME types
     * @return self
     */
    public function setAllowedTypes(array $types): self
    {
        $this->allowedTypes = $types;
        return $this;
    }

    public function setMaxFileSize(int $bytes): self
    {
        $this->maxFileSize = $bytes;
        return $this;
    }

    public function setTargetFolder(string $folder): self
    {
        $this->targetFolder = rtrim($folder, '/') . '/';
        return $this;
    }

    public function upload(string $fileInputName): array
    {
        // Check if file was uploaded
        if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
            $error = $this->getUploadErrorMessage($_FILES[$fileInputName]['error'] ?? UPLOAD_ERR_NO_FILE);
            $this->lastError = $error;
            return ['success' => false, 'fileName' => '', 'error' => $error];
        }

        $file = $_FILES[$fileInputName];
        $tempName = $file['tmp_name'];
        $originalName = $file['name'];
        $fileSize = $file['size'];

        // Validate file size
        if ($fileSize > $this->maxFileSize) {
            $error = 'File size exceeds maximum allowed size of ' . $this->formatBytes($this->maxFileSize);
            $this->lastError = $error;
            return ['success' => false, 'fileName' => '', 'error' => $error];
        }

        // Validate file type
        $fileType = mime_content_type($tempName);
        if (!in_array($fileType, $this->allowedTypes)) {
            $error = 'Invalid file type. Allowed types: ' . implode(', ', $this->getReadableTypes());
            $this->lastError = $error;
            return ['success' => false, 'fileName' => '', 'error' => $error];
        }

        // Generate unique filename
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $uniqueName = uniqid('room_', true) . '.' . strtolower($extension);

        // Ensure target folder exists
        if (!$this->ensureDirectoryExists()) {
            $error = 'Failed to create upload directory';
            $this->lastError = $error;
            return ['success' => false, 'fileName' => '', 'error' => $error];
        }

        $targetPath = $this->targetFolder . $uniqueName;

        // Move uploaded file
        if (move_uploaded_file($tempName, $targetPath)) {
            return ['success' => true, 'fileName' => $uniqueName, 'error' => ''];
        }

        $error = 'Failed to move uploaded file. Check folder permissions.';
        $this->lastError = $error;
        return ['success' => false, 'fileName' => '', 'error' => $error];
    }

    public function delete(string $fileName): bool
    {
        if (empty($fileName)) {
            return false;
        }

        $filePath = $this->targetFolder . $fileName;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    public function exists(string $fileName): bool
    {
        return file_exists($this->targetFolder . $fileName);
    }

    public function getFullPath(string $fileName): string
    {
        return $this->targetFolder . $fileName;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function ensureDirectoryExists(): bool
    {
        if (is_dir($this->targetFolder)) {
            return true;
        }

        return mkdir($this->targetFolder, 0755, true);
    }

    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Get readable file types from MIME types
     * 
     * @return array
     */
    private function getReadableTypes(): array
    {
        $readable = [];
        foreach ($this->allowedTypes as $type) {
            $parts = explode('/', $type);
            $readable[] = strtoupper($parts[1] ?? $type);
        }
        return array_unique($readable);
    }

    /**
     * format bytes to human readable format
     * 
     * @param int $bytes Bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
