<?php
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>POST Data:</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h2>Field Count Check:</h2>";
echo "paper_id: " . (isset($_POST['paper_id']) ? $_POST['paper_id'] : 'NOT SET') . "<br>";
echo "paper_type: " . (isset($_POST['paper_type']) ? $_POST['paper_type'] : 'NOT SET') . "<br>";
echo "code: " . (isset($_POST['code']) ? $_POST['code'] : 'NOT SET') . "<br>";
echo "lot: " . (isset($_POST['lot']) ? $_POST['lot'] : 'NOT SET') . "<br>";
echo "supplier_name: " . (isset($_POST['supplier_name']) ? $_POST['supplier_name'] : 'NOT SET') . "<br>";
echo "return_date: " . (isset($_POST['return_date']) ? $_POST['return_date'] : 'NOT SET') . "<br>";
echo "receiving_date: " . (isset($_POST['receiving_date']) ? $_POST['receiving_date'] : 'NOT SET') . "<br>";
echo "tender_file_no: " . (isset($_POST['tender_file_no']) ? $_POST['tender_file_no'] : 'NOT SET') . "<br>";
echo "location: " . (isset($_POST['location']) ? $_POST['location'] : 'NOT SET') . "<br>";
echo "invoice: " . (isset($_POST['invoice']) ? $_POST['invoice'] : 'NOT SET') . "<br>";
echo "return_by: " . (isset($_POST['return_by']) ? $_POST['return_by'] : 'NOT SET') . "<br>";
echo "quantity: " . (isset($_POST['quantity']) ? $_POST['quantity'] : 'NOT SET') . "<br>";
echo "reason: " . (isset($_POST['reason']) ? $_POST['reason'] : 'NOT SET') . "<br>";
echo "remarks: " . (isset($_POST['remarks']) ? $_POST['remarks'] : 'NOT SET') . "<br>";
?>
