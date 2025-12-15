<?php
/**
 * Document Management System
 * Advanced document handling with versioning, metadata, OCR, and signatures
 * 
 * @package SLPA\DocumentManagement
 * @version 1.0.0
 */

class DocumentManager {
    private $db;
    private $uploadPath;
    private $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt'];
    private $maxFileSize = 10485760; // 10MB
    
    public function __construct($uploadPath = null) {
        $this->db = Database::getInstance();
        $this->uploadPath = $uploadPath ?? BASE_PATH . '/uploads/documents';
        
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }
    
    /**
     * Upload document
     */
    public function upload($file, $metadata = []) {
        // Validate file
        $validation = $this->validateFile($file);
        if ($validation !== true) {
            throw new Exception($validation);
        }
        
        $filename = $this->generateFilename($file['name']);
        $filepath = $this->uploadPath . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception("Failed to upload file");
        }
        
        // Save to database
        $documentId = $this->saveDocument($filename, $file, $metadata);
        
        // Extract text if supported
        if (in_array($this->getFileExtension($file['name']), ['pdf', 'doc', 'docx'])) {
            $this->extractText($documentId, $filepath);
        }
        
        return $documentId;
    }
    
    /**
     * Validate file
     */
    private function validateFile($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return "File upload error: " . $file['error'];
        }
        
        if ($file['size'] > $this->maxFileSize) {
            return "File size exceeds maximum allowed size";
        }
        
        $ext = $this->getFileExtension($file['name']);
        if (!in_array($ext, $this->allowedTypes)) {
            return "File type not allowed";
        }
        
        return true;
    }
    
    /**
     * Generate unique filename
     */
    private function generateFilename($originalName) {
        $ext = $this->getFileExtension($originalName);
        return uniqid() . '_' . time() . '.' . $ext;
    }
    
    /**
     * Get file extension
     */
    private function getFileExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Save document to database
     */
    private function saveDocument($filename, $file, $metadata) {
        $conn = $this->db->getConnection();
        
        $originalName = $file['name'];
        $fileSize = $file['size'];
        $mimeType = $file['type'];
        $userId = $_SESSION['user_id'];
        
        $title = $metadata['title'] ?? $originalName;
        $description = $metadata['description'] ?? '';
        $category = $metadata['category'] ?? 'general';
        $tags = json_encode($metadata['tags'] ?? []);
        
        $sql = "INSERT INTO documents (user_id, title, description, filename, original_name, 
                file_size, mime_type, category, tags, version, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issssiss', $userId, $title, $description, $filename, 
                         $originalName, $fileSize, $mimeType, $category, $tags);
        $stmt->execute();
        
        return $conn->insert_id;
    }
    
    /**
     * Get document
     */
    public function getDocument($documentId) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM documents WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $documentId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Download document
     */
    public function download($documentId) {
        $document = $this->getDocument($documentId);
        
        if (!$document) {
            throw new Exception("Document not found");
        }
        
        $filepath = $this->uploadPath . '/' . $document['filename'];
        
        if (!file_exists($filepath)) {
            throw new Exception("File not found");
        }
        
        // Log download
        $this->logAccess($documentId, 'download');
        
        header('Content-Type: ' . $document['mime_type']);
        header('Content-Disposition: attachment; filename="' . $document['original_name'] . '"');
        header('Content-Length: ' . filesize($filepath));
        
        readfile($filepath);
        exit;
    }
    
    /**
     * Create new version
     */
    public function createVersion($documentId, $file, $comment = '') {
        $oldDocument = $this->getDocument($documentId);
        
        if (!$oldDocument) {
            throw new Exception("Document not found");
        }
        
        // Archive old version
        $this->archiveVersion($documentId, $oldDocument['filename'], $oldDocument['version']);
        
        // Upload new version
        $filename = $this->generateFilename($file['name']);
        $filepath = $this->uploadPath . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception("Failed to upload new version");
        }
        
        // Update document
        $conn = $this->db->getConnection();
        $newVersion = $oldDocument['version'] + 1;
        
        $sql = "UPDATE documents SET filename = ?, file_size = ?, version = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('siii', $filename, $file['size'], $newVersion, $documentId);
        $stmt->execute();
        
        // Log version
        $this->logVersion($documentId, $newVersion, $comment);
        
        return $newVersion;
    }
    
    /**
     * Archive version
     */
    private function archiveVersion($documentId, $filename, $version) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO document_versions (document_id, filename, version, created_at) 
                VALUES (?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isi', $documentId, $filename, $version);
        $stmt->execute();
        
        // Move file to archive
        $archivePath = $this->uploadPath . '/archive';
        if (!is_dir($archivePath)) {
            mkdir($archivePath, 0755, true);
        }
        
        $oldPath = $this->uploadPath . '/' . $filename;
        $newPath = $archivePath . '/' . $filename;
        
        if (file_exists($oldPath)) {
            rename($oldPath, $newPath);
        }
    }
    
    /**
     * Get version history
     */
    public function getVersionHistory($documentId) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT v.*, u.username 
                FROM document_versions v
                LEFT JOIN document_version_log l ON v.document_id = l.document_id AND v.version = l.version
                LEFT JOIN users u ON l.user_id = u.id
                WHERE v.document_id = ? 
                ORDER BY v.version DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $documentId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $versions = [];
        
        while ($row = $result->fetch_assoc()) {
            $versions[] = $row;
        }
        
        return $versions;
    }
    
    /**
     * Log version change
     */
    private function logVersion($documentId, $version, $comment) {
        $conn = $this->db->getConnection();
        $userId = $_SESSION['user_id'];
        
        $sql = "INSERT INTO document_version_log (document_id, version, user_id, comment, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiis', $documentId, $version, $userId, $comment);
        $stmt->execute();
    }
    
    /**
     * Search documents
     */
    public function search($query, $filters = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT d.*, u.username as uploaded_by 
                FROM documents d
                JOIN users u ON d.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if ($query) {
            $sql .= " AND (d.title LIKE ? OR d.description LIKE ? OR d.extracted_text LIKE ?)";
            $searchTerm = "%$query%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        if (isset($filters['category'])) {
            $sql .= " AND d.category = ?";
            $params[] = $filters['category'];
            $types .= 's';
        }
        
        if (isset($filters['user_id'])) {
            $sql .= " AND d.user_id = ?";
            $params[] = $filters['user_id'];
            $types .= 'i';
        }
        
        $sql .= " ORDER BY d.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        
        $result = $stmt->get_result();
        $documents = [];
        
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
        
        return $documents;
    }
    
    /**
     * Extract text from document (OCR/Text extraction)
     */
    private function extractText($documentId, $filepath) {
        $ext = $this->getFileExtension($filepath);
        $text = '';
        
        try {
            switch ($ext) {
                case 'pdf':
                    $text = $this->extractTextFromPDF($filepath);
                    break;
                case 'doc':
                case 'docx':
                    $text = $this->extractTextFromWord($filepath);
                    break;
            }
            
            if ($text) {
                $this->saveExtractedText($documentId, $text);
            }
        } catch (Exception $e) {
            // Log error but don't fail
            error_log("Text extraction failed: " . $e->getMessage());
        }
    }
    
    /**
     * Extract text from PDF
     */
    private function extractTextFromPDF($filepath) {
        // Using pdftotext command if available
        $output = shell_exec("pdftotext '$filepath' -");
        return $output ?? '';
    }
    
    /**
     * Extract text from Word document
     */
    private function extractTextFromWord($filepath) {
        // Using antiword or similar tool
        $output = shell_exec("antiword '$filepath'");
        return $output ?? '';
    }
    
    /**
     * Save extracted text
     */
    private function saveExtractedText($documentId, $text) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE documents SET extracted_text = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $text, $documentId);
        $stmt->execute();
    }
    
    /**
     * Add digital signature
     */
    public function addSignature($documentId, $signatureData) {
        $conn = $this->db->getConnection();
        $userId = $_SESSION['user_id'];
        
        $sql = "INSERT INTO document_signatures (document_id, user_id, signature_data, signed_at) 
                VALUES (?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $documentId, $userId, $signatureData);
        $stmt->execute();
        
        // Update document status
        $this->updateDocumentStatus($documentId, 'signed');
        
        return $conn->insert_id;
    }
    
    /**
     * Verify signature
     */
    public function verifySignature($signatureId) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT s.*, u.username, d.filename 
                FROM document_signatures s
                JOIN users u ON s.user_id = u.id
                JOIN documents d ON s.document_id = d.id
                WHERE s.id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $signatureId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get document signatures
     */
    public function getSignatures($documentId) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT s.*, u.username, u.email 
                FROM document_signatures s
                JOIN users u ON s.user_id = u.id
                WHERE s.document_id = ?
                ORDER BY s.signed_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $documentId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $signatures = [];
        
        while ($row = $result->fetch_assoc()) {
            $signatures[] = $row;
        }
        
        return $signatures;
    }
    
    /**
     * Update document status
     */
    private function updateDocumentStatus($documentId, $status) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE documents SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $status, $documentId);
        $stmt->execute();
    }
    
    /**
     * Log document access
     */
    private function logAccess($documentId, $action) {
        $conn = $this->db->getConnection();
        $userId = $_SESSION['user_id'];
        
        $sql = "INSERT INTO document_access_log (document_id, user_id, action, created_at) 
                VALUES (?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $documentId, $userId, $action);
        $stmt->execute();
    }
    
    /**
     * Get access log
     */
    public function getAccessLog($documentId) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT l.*, u.username 
                FROM document_access_log l
                JOIN users u ON l.user_id = u.id
                WHERE l.document_id = ?
                ORDER BY l.created_at DESC
                LIMIT 50";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $documentId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $logs = [];
        
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        
        return $logs;
    }
    
    /**
     * Update metadata
     */
    public function updateMetadata($documentId, $metadata) {
        $conn = $this->db->getConnection();
        
        $updates = [];
        $values = [];
        $types = '';
        
        if (isset($metadata['title'])) {
            $updates[] = "title = ?";
            $values[] = $metadata['title'];
            $types .= 's';
        }
        
        if (isset($metadata['description'])) {
            $updates[] = "description = ?";
            $values[] = $metadata['description'];
            $types .= 's';
        }
        
        if (isset($metadata['category'])) {
            $updates[] = "category = ?";
            $values[] = $metadata['category'];
            $types .= 's';
        }
        
        if (isset($metadata['tags'])) {
            $updates[] = "tags = ?";
            $values[] = json_encode($metadata['tags']);
            $types .= 's';
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $values[] = $documentId;
        $types .= 'i';
        
        $sql = "UPDATE documents SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        
        return true;
    }
    
    /**
     * Delete document
     */
    public function delete($documentId) {
        $document = $this->getDocument($documentId);
        
        if (!$document) {
            throw new Exception("Document not found");
        }
        
        // Delete file
        $filepath = $this->uploadPath . '/' . $document['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Delete from database
        $conn = $this->db->getConnection();
        
        $sql = "DELETE FROM documents WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $documentId);
        $stmt->execute();
        
        return true;
    }
}

/**
 * Document Workflow
 */
class DocumentWorkflow {
    private $db;
    private $workflowEngine;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->workflowEngine = new WorkflowEngine();
    }
    
    /**
     * Submit document for approval
     */
    public function submitForApproval($documentId, $approvers) {
        $workflowBuilder = new WorkflowBuilder();
        
        $workflow = $workflowBuilder
            ->setName('Document Approval')
            ->setDescription("Approval workflow for document #$documentId");
        
        foreach ($approvers as $approver) {
            $workflow->addApprovalStep($approver, "Please review and approve document #$documentId");
        }
        
        $workflow->addNotificationStep($_SESSION['user_id'], 'Document Approved', 'Your document has been approved');
        
        $workflowId = $workflow->save();
        
        // Start workflow
        $instanceId = $this->workflowEngine->startWorkflow($workflowId, [
            'document_id' => $documentId
        ], $_SESSION['user_id']);
        
        // Update document status
        $conn = $this->db->getConnection();
        $sql = "UPDATE documents SET status = 'pending_approval', workflow_instance_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $instanceId, $documentId);
        $stmt->execute();
        
        return $instanceId;
    }
}
