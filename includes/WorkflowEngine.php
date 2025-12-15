<?php
/**
 * Workflow Automation Engine
 * Professional workflow management with approval chains and automation
 * 
 * @package SLPA\Workflow
 * @version 1.0.0
 */

class WorkflowEngine {
    private $db;
    private $logger;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }
    
    /**
     * Start workflow
     */
    public function startWorkflow($workflowId, $data, $initiatedBy) {
        $conn = $this->db->getConnection();
        
        // Get workflow definition
        $workflow = $this->getWorkflow($workflowId);
        
        // Create workflow instance
        $sql = "INSERT INTO workflow_instances 
                (workflow_id, data, status, initiated_by, created_at) 
                VALUES (?, ?, 'pending', ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $dataJson = json_encode($data);
        $stmt->bind_param('isi', $workflowId, $dataJson, $initiatedBy);
        $stmt->execute();
        
        $instanceId = $conn->insert_id;
        
        // Start first step
        $this->executeNextStep($instanceId);
        
        $this->logger->info("Workflow started", [
            'workflow_id' => $workflowId,
            'instance_id' => $instanceId,
            'initiated_by' => $initiatedBy
        ]);
        
        return $instanceId;
    }
    
    /**
     * Execute next workflow step
     */
    private function executeNextStep($instanceId) {
        $conn = $this->db->getConnection();
        
        // Get current instance
        $sql = "SELECT * FROM workflow_instances WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $instanceId);
        $stmt->execute();
        $instance = $stmt->get_result()->fetch_assoc();
        
        // Get workflow steps
        $workflow = $this->getWorkflow($instance['workflow_id']);
        $steps = json_decode($workflow['steps'], true);
        
        // Find next step
        $currentStep = $instance['current_step'] ?? 0;
        
        if ($currentStep >= count($steps)) {
            // Workflow complete
            $this->completeWorkflow($instanceId);
            return;
        }
        
        $step = $steps[$currentStep];
        
        // Create step instance
        $sql = "INSERT INTO workflow_step_instances 
                (instance_id, step_number, step_type, assignee, data, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($sql);
        $stepData = json_encode($step);
        $stmt->bind_param('iisis', $instanceId, $currentStep, $step['type'], $step['assignee'], $stepData);
        $stmt->execute();
        
        $stepInstanceId = $conn->insert_id;
        
        // Update workflow instance
        $sql = "UPDATE workflow_instances SET current_step = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $currentStep, $instanceId);
        $stmt->execute();
        
        // Execute step action
        $this->executeStepAction($stepInstanceId, $step);
        
        return $stepInstanceId;
    }
    
    /**
     * Execute step action
     */
    private function executeStepAction($stepInstanceId, $step) {
        switch ($step['type']) {
            case 'approval':
                $this->sendApprovalNotification($stepInstanceId, $step);
                break;
                
            case 'notification':
                $this->sendNotification($stepInstanceId, $step);
                $this->approveStep($stepInstanceId, $step['assignee'], 'auto-approved');
                break;
                
            case 'task':
                $this->createTask($stepInstanceId, $step);
                break;
                
            case 'script':
                $this->executeScript($stepInstanceId, $step);
                break;
                
            default:
                throw new Exception("Unknown step type: {$step['type']}");
        }
    }
    
    /**
     * Approve workflow step
     */
    public function approveStep($stepInstanceId, $approverId, $comments = '') {
        $conn = $this->db->getConnection();
        
        // Update step instance
        $sql = "UPDATE workflow_step_instances 
                SET status = 'approved', approved_by = ?, approved_at = NOW(), comments = ? 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isi', $approverId, $comments, $stepInstanceId);
        $stmt->execute();
        
        // Get instance
        $sql = "SELECT instance_id FROM workflow_step_instances WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $stepInstanceId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $instanceId = $result['instance_id'];
        
        // Execute next step
        $this->executeNextStep($instanceId);
        
        $this->logger->info("Workflow step approved", [
            'step_instance_id' => $stepInstanceId,
            'approved_by' => $approverId
        ]);
    }
    
    /**
     * Reject workflow step
     */
    public function rejectStep($stepInstanceId, $rejectedBy, $reason) {
        $conn = $this->db->getConnection();
        
        // Update step instance
        $sql = "UPDATE workflow_step_instances 
                SET status = 'rejected', rejected_by = ?, rejected_at = NOW(), rejection_reason = ? 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isi', $rejectedBy, $reason, $stepInstanceId);
        $stmt->execute();
        
        // Get instance
        $sql = "SELECT instance_id FROM workflow_step_instances WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $stepInstanceId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $instanceId = $result['instance_id'];
        
        // Update workflow instance
        $sql = "UPDATE workflow_instances SET status = 'rejected', updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $instanceId);
        $stmt->execute();
        
        $this->logger->info("Workflow step rejected", [
            'step_instance_id' => $stepInstanceId,
            'rejected_by' => $rejectedBy,
            'reason' => $reason
        ]);
    }
    
    /**
     * Complete workflow
     */
    private function completeWorkflow($instanceId) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE workflow_instances 
                SET status = 'completed', completed_at = NOW() 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $instanceId);
        $stmt->execute();
        
        $this->logger->info("Workflow completed", ['instance_id' => $instanceId]);
    }
    
    /**
     * Get workflow definition
     */
    private function getWorkflow($workflowId) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM workflows WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $workflowId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $workflow = $result->fetch_assoc();
        
        if (!$workflow) {
            throw new Exception("Workflow not found: $workflowId");
        }
        
        return $workflow;
    }
    
    /**
     * Send approval notification
     */
    private function sendApprovalNotification($stepInstanceId, $step) {
        // Log approval notification
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO notifications (user_id, type, title, message, created_at) 
                VALUES (?, 'workflow_approval', 'Approval Required', ?, NOW())";
        $stmt = $conn->prepare($sql);
        $message = "You have a workflow approval pending. Step: {$step['name']}";
        $userId = $step['assignee'];
        $stmt->bind_param('is', $userId, $message);
        $stmt->execute();
    }
    
    /**
     * Send notification
     */
    private function sendNotification($stepInstanceId, $step) {
        // Log notification
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO notifications (user_id, type, title, message, created_at) 
                VALUES (?, 'workflow_notification', ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $userId = $step['assignee'];
        $title = $step['name'];
        $message = $step['message'] ?? 'Workflow notification';
        $stmt->bind_param('iss', $userId, $title, $message);
        $stmt->execute();
    }
    
    /**
     * Create task
     */
    private function createTask($stepInstanceId, $step) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO tasks 
                (title, description, assigned_to, due_date, status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($sql);
        $dueDate = isset($step['due_days']) ? date('Y-m-d', strtotime("+{$step['due_days']} days")) : null;
        
        $stmt->bind_param('ssis', 
            $step['name'], 
            $step['description'] ?? '', 
            $step['assignee'], 
            $dueDate
        );
        $stmt->execute();
    }
    
    /**
     * Execute script
     */
    private function executeScript($stepInstanceId, $step) {
        if (isset($step['script'])) {
            // Execute custom PHP script
            eval($step['script']);
        }
        
        // Auto-approve script steps
        $this->approveStep($stepInstanceId, 0, 'auto-approved');
    }
    
    /**
     * Get pending approvals for user
     */
    public function getPendingApprovals($userId) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT wsi.*, wi.workflow_id, w.name as workflow_name, wi.data as workflow_data
                FROM workflow_step_instances wsi
                JOIN workflow_instances wi ON wsi.instance_id = wi.id
                JOIN workflows w ON wi.workflow_id = w.id
                WHERE wsi.assignee = ? AND wsi.status = 'pending'
                ORDER BY wsi.created_at ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $approvals = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['data'] = json_decode($row['data'], true);
            $row['workflow_data'] = json_decode($row['workflow_data'], true);
            $approvals[] = $row;
        }
        
        return $approvals;
    }
}

/**
 * Workflow Builder
 */
class WorkflowBuilder {
    private $name;
    private $description;
    private $steps = [];
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Set workflow name
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }
    
    /**
     * Set description
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }
    
    /**
     * Add approval step
     */
    public function addApprovalStep($name, $assignee, $description = '') {
        $this->steps[] = [
            'type' => 'approval',
            'name' => $name,
            'assignee' => $assignee,
            'description' => $description
        ];
        return $this;
    }
    
    /**
     * Add notification step
     */
    public function addNotificationStep($name, $recipient, $message) {
        $this->steps[] = [
            'type' => 'notification',
            'name' => $name,
            'assignee' => $recipient,
            'message' => $message
        ];
        return $this;
    }
    
    /**
     * Add task step
     */
    public function addTaskStep($name, $assignee, $description, $dueDays = 7) {
        $this->steps[] = [
            'type' => 'task',
            'name' => $name,
            'assignee' => $assignee,
            'description' => $description,
            'due_days' => $dueDays
        ];
        return $this;
    }
    
    /**
     * Save workflow
     */
    public function save() {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO workflows (name, description, steps, is_active, created_at) 
                VALUES (?, ?, ?, 1, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stepsJson = json_encode($this->steps);
        
        $stmt->bind_param('sss', $this->name, $this->description, $stepsJson);
        $stmt->execute();
        
        return $conn->insert_id;
    }
}

/**
 * Workflow Templates
 */
class WorkflowTemplate {
    /**
     * Purchase Order Approval Workflow
     */
    public static function purchaseOrderApproval() {
        return (new WorkflowBuilder())
            ->setName('Purchase Order Approval')
            ->setDescription('Standard purchase order approval workflow')
            ->addApprovalStep('Department Manager Approval', 'manager', 'Review purchase request')
            ->addApprovalStep('Finance Approval', 'finance', 'Verify budget allocation')
            ->addApprovalStep('Director Approval', 'director', 'Final approval')
            ->addNotificationStep('Notify Procurement', 'procurement', 'PO approved, proceed with purchase')
            ->save();
    }
    
    /**
     * Inventory Requisition Workflow
     */
    public static function inventoryRequisition() {
        return (new WorkflowBuilder())
            ->setName('Inventory Requisition')
            ->setDescription('Inventory item requisition workflow')
            ->addApprovalStep('Supervisor Approval', 'supervisor', 'Review requisition')
            ->addTaskStep('Prepare Items', 'warehouse', 'Prepare items for dispatch', 2)
            ->addNotificationStep('Notify Requester', 'requester', 'Items ready for collection')
            ->save();
    }
}
