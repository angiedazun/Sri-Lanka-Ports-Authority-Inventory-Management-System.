<?php
require_once '../includes/db.php';
require_login();

header('Content-Type: application/json');

// Get parameters
$report_type = $_GET['report_type'] ?? 'all';
$filter_type = $_GET['filter_type'] ?? 'all';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';
$format = $_GET['format'] ?? 'json';

// Get advanced filter parameters
$supplier = $_GET['supplier'] ?? '';
$item = $_GET['item'] ?? '';
$is_code = $_GET['is_code'] ?? '';
$lot = $_GET['lot'] ?? '';
$division = $_GET['division'] ?? '';
$group_by = $_GET['group_by'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'date_desc';
$include_charts = $_GET['include_charts'] ?? 'false';
$detail_level = $_GET['detail_level'] ?? 'full';

// Build date filter
$date_filter = '';
$date_range_text = 'All Time';

if ($filter_type == 'daterange' && $start_date && $end_date) {
    $date_filter = " AND DATE(receive_date) BETWEEN '$start_date' AND '$end_date'";
    $date_range_text = date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date));
} elseif ($filter_type == 'year' && $year) {
    $date_filter = " AND YEAR(receive_date) = '$year'";
    $date_range_text = "Year $year";
} elseif ($filter_type == 'month' && $month && $year) {
    $date_filter = " AND YEAR(receive_date) = '$year' AND MONTH(receive_date) = '$month'";
    $date_range_text = date('F Y', strtotime("$year-$month-01"));
}

// Build advanced filters
$supplier_filter = '';
$item_filter = '';
$is_code_filter = '';
$lot_filter = '';
$division_filter = '';

if (!empty($supplier)) {
    $supplier_escaped = $conn->real_escape_string($supplier);
    $supplier_filter = " AND supplier_name LIKE '%$supplier_escaped%'";
}

if (!empty($item)) {
    $item_escaped = $conn->real_escape_string($item);
    $item_filter = " AND (paper_type LIKE '%$item_escaped%' OR toner_model LIKE '%$item_escaped%' OR ribbon_model LIKE '%$item_escaped%')";
}

if (!empty($is_code)) {
    $is_code_escaped = $conn->real_escape_string($is_code);
    $is_code_filter = " AND code LIKE '%$is_code_escaped%'";
}

if (!empty($lot)) {
    $lot_escaped = $conn->real_escape_string($lot);
    $lot_filter = " AND lot = '$lot_escaped'";
}

if (!empty($division)) {
    $division_escaped = $conn->real_escape_string($division);
    $division_filter = " AND division = '$division_escaped'";
}

// Build ORDER BY clause
$order_clause = '';
switch ($sort_by) {
    case 'date_asc':
        $order_clause = ' ORDER BY date_col ASC';
        break;
    case 'date_desc':
        $order_clause = ' ORDER BY date_col DESC';
        break;
    case 'quantity_asc':
        $order_clause = ' ORDER BY quantity_col ASC';
        break;
    case 'quantity_desc':
        $order_clause = ' ORDER BY quantity_col DESC';
        break;
    case 'value_asc':
        $order_clause = ' ORDER BY value_col ASC';
        break;
    case 'value_desc':
        $order_clause = ' ORDER BY value_col DESC';
        break;
    default:
        $order_clause = ' ORDER BY date_col DESC';
}

$response = [
    'success' => true,
    'report_title' => '',
    'report_type_label' => '',
    'date_range' => $date_range_text,
    'total_records' => 0,
    'generated_by' => $_SESSION['username'] ?? 'System',
    'summary' => [],
    'columns' => [],
    'records' => []
];

try {
    switch ($report_type) {
        case 'papers':
            // Combined Papers Report (Receiving + Issuing + Returns)
            $response['report_title'] = 'Papers Management Report';
            $response['report_type_label'] = 'Papers Management';
            $response['columns'] = ['Date', 'Type', 'Paper Type', 'LOT', 'Supplier', 'PR No', 'File No', 'Division', 'Receiver Name', 'Quantity'];
            
            $all_records = [];
            $total_receiving = 0;
            $total_issuing = 0;
            $total_returns = 0;
            
            // Get Papers Receiving
            $date_filter_papers = $date_filter;
            $query = "SELECT receive_date as date, paper_type, lot, supplier_name, tender_file_no, invoice,
                     (jct_quantity + uct_quantity) as quantity, 'Receiving' as type
                     FROM papers_receiving WHERE 1=1 $date_filter_papers $supplier_filter $lot_filter";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND paper_type LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total_receiving++;
                $all_records[] = [
                    'date' => strtotime($row['date']),
                    'data' => [
                        date('M d, Y', strtotime($row['date'])),
                        '<span class="badge badge-success">📥 Receiving</span>',
                        $row['paper_type'] ?: 'N/A',
                        $row['lot'] ?: 'N/A',
                        $row['supplier_name'] ?: 'N/A',
                        $row['tender_file_no'] ?: 'N/A',
                        $row['invoice'] ?: 'N/A',
                        '-',
                        '-',
                        $row['quantity']
                    ]
                ];
            }
            
            // Get Papers Issuing with Receiver Details
            $date_filter_issue = str_replace('receive_date', 'issue_date', $date_filter);
            $query = "SELECT pi.issue_date as date, pi.paper_type, pi.lot, pi.division, pi.quantity,
                     pi.receiver_name, pi.receiver_emp_no, pr.supplier_name, pr.tender_file_no, pr.invoice,
                     'Issuing' as type
                     FROM papers_issuing pi
                     LEFT JOIN papers_receiving pr ON pi.lot = pr.lot AND pi.paper_id = pr.paper_id
                     WHERE 1=1 $date_filter_issue $lot_filter $division_filter";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND pi.paper_type LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total_issuing++;
                $receiver_info = $row['receiver_name'] ?: 'N/A';
                if (!empty($row['receiver_emp_no'])) {
                    $receiver_info .= ' (' . $row['receiver_emp_no'] . ')';
                }
                
                $all_records[] = [
                    'date' => strtotime($row['date']),
                    'data' => [
                        date('M d, Y', strtotime($row['date'])),
                        '<span class="badge badge-primary">📤 Issuing</span>',
                        $row['paper_type'] ?: 'N/A',
                        $row['lot'] ?: 'N/A',
                        $row['supplier_name'] ?: 'N/A',
                        $row['tender_file_no'] ?: 'N/A',
                        $row['invoice'] ?: 'N/A',
                        $row['division'] ?: 'N/A',
                        $receiver_info,
                        $row['quantity']
                    ]
                ];
            }
            
            // Get Papers Returns with Full Details
            $date_filter_return = str_replace('receive_date', 'return_date', $date_filter);
            $query = "SELECT 
                pr.return_date as date, 
                pr.paper_type, 
                pr.lot, 
                COALESCE(NULLIF(pr.supplier_name, ''), prec.supplier_name, 'N/A') as supplier_name,
                COALESCE(pr.tender_file_no, prec.tender_file_no, 'N/A') as tender_file_no,
                COALESCE(pr.invoice, prec.invoice, 'N/A') as invoice,
                pr.return_by,
                pr.quantity, 
                'Return' as type
            FROM papers_return pr
            LEFT JOIN papers_receiving prec ON pr.lot = prec.lot AND pr.paper_id = prec.paper_id
            WHERE 1=1 $date_filter_return $lot_filter";
            if (!empty($supplier)) {
                $supplier_escaped = $conn->real_escape_string($supplier);
                $query .= " AND (pr.supplier_name LIKE '%$supplier_escaped%' OR prec.supplier_name LIKE '%$supplier_escaped%')";
            }
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND pr.paper_type LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total_returns++;
                $all_records[] = [
                    'date' => strtotime($row['date']),
                    'data' => [
                        date('M d, Y', strtotime($row['date'])),
                        '<span class="badge badge-danger">🔙 Return</span>',
                        $row['paper_type'] ?: 'N/A',
                        $row['lot'] ?: 'N/A',
                        $row['supplier_name'],
                        $row['tender_file_no'],
                        $row['invoice'],
                        '-',
                        $row['return_by'] ?: 'N/A',
                        $row['quantity']
                    ]
                ];
            }
            
            // Sort by date descending
            usort($all_records, function($a, $b) {
                return $b['date'] - $a['date'];
            });
            
            foreach ($all_records as $record) {
                $response['records'][] = $record['data'];
            }
            
            $response['total_records'] = count($response['records']);
            $response['summary'] = [
                'receiving_count' => $total_receiving,
                'issuing_count' => $total_issuing,
                'return_count' => $total_returns
            ];
            break;
            
        case 'toner':
            // Combined Toner Report (Receiving + Issuing + Returns)
            $response['report_title'] = 'Toner Management Report';
            $response['report_type_label'] = 'Toner Management';
            $response['columns'] = ['Date', 'Type', 'Model', 'LOT', 'Supplier', 'PR No', 'File No', 'Division', 'Receiver Name', 'Quantity'];
            
            $all_records = [];
            $total_receiving = 0;
            $total_issuing = 0;
            $total_returns = 0;
            
            // Get Toner Receiving
            $date_filter_toner = $date_filter;
            $query = "SELECT receive_date as date, toner_model, lot, supplier_name, tender_file_no, invoice, 
                     (jct_quantity + uct_quantity) as quantity, 'Receiving' as type
                     FROM toner_receiving WHERE 1=1 $date_filter_toner $supplier_filter $lot_filter";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND toner_model LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total_receiving++;
                $all_records[] = [
                    'date' => strtotime($row['date']),
                    'data' => [
                        date('M d, Y', strtotime($row['date'])),
                        '<span class="badge badge-success">📥 Receiving</span>',
                        $row['toner_model'] ?: 'N/A',
                        $row['lot'] ?: 'N/A',
                        $row['supplier_name'] ?: 'N/A',
                        $row['tender_file_no'] ?: 'N/A',
                        $row['invoice'] ?: 'N/A',
                        '-',
                        '-',
                        $row['quantity']
                    ]
                ];
            }
            
            // Get Toner Issuing with Receiver Details
            $date_filter_issue = str_replace('receive_date', 'issue_date', $date_filter);
            $query = "SELECT ti.issue_date as date, ti.toner_model, ti.lot, ti.division, ti.quantity, 
                     ti.receiver_name, ti.receiver_emp_no, tr.supplier_name, tr.tender_file_no, tr.invoice,
                     'Issuing' as type
                     FROM toner_issuing ti
                     LEFT JOIN toner_receiving tr ON ti.lot = tr.lot AND ti.toner_id = tr.toner_id
                     WHERE 1=1 $date_filter_issue $lot_filter $division_filter";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND ti.toner_model LIKE '%$item_escaped%'";
            }
            if (!empty($is_code)) {
                $is_code_escaped = $conn->real_escape_string($is_code);
                $query .= " AND ti.code LIKE '%$is_code_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total_issuing++;
                $receiver_info = $row['receiver_name'] ?: 'N/A';
                if (!empty($row['receiver_emp_no'])) {
                    $receiver_info .= ' (' . $row['receiver_emp_no'] . ')';
                }
                
                $all_records[] = [
                    'date' => strtotime($row['date']),
                    'data' => [
                        date('M d, Y', strtotime($row['date'])),
                        '<span class="badge badge-primary">📤 Issuing</span>',
                        $row['toner_model'] ?: 'N/A',
                        $row['lot'] ?: 'N/A',
                        $row['supplier_name'] ?: 'N/A',
                        $row['tender_file_no'] ?: 'N/A',
                        $row['invoice'] ?: 'N/A',
                        $row['division'] ?: 'N/A',
                        $receiver_info,
                        $row['quantity']
                    ]
                ];
            }
            
            // Get Toner Returns with Full Details
            $date_filter_return = str_replace('receive_date', 'return_date', $date_filter);
            $query = "SELECT 
                tr.return_date as date, 
                tr.toner_model, 
                tr.lot, 
                COALESCE(NULLIF(tr.supplier_name, ''), trec.supplier_name, 'N/A') as supplier_name,
                COALESCE(tr.tender_file_no, trec.tender_file_no, 'N/A') as tender_file_no,
                COALESCE(tr.invoice, trec.invoice, 'N/A') as invoice,
                tr.returned_by,
                tr.quantity, 
                'Return' as type
            FROM toner_return tr
            LEFT JOIN toner_receiving trec ON tr.lot = trec.lot AND tr.toner_id = trec.toner_id
            WHERE 1=1 $date_filter_return $lot_filter";
            if (!empty($supplier)) {
                $supplier_escaped = $conn->real_escape_string($supplier);
                $query .= " AND (tr.supplier_name LIKE '%$supplier_escaped%' OR trec.supplier_name LIKE '%$supplier_escaped%')";
            }
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND tr.toner_model LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total_returns++;
                $all_records[] = [
                    'date' => strtotime($row['date']),
                    'data' => [
                        date('M d, Y', strtotime($row['date'])),
                        '<span class="badge badge-danger">🔙 Return</span>',
                        $row['toner_model'] ?: 'N/A',
                        $row['lot'] ?: 'N/A',
                        $row['supplier_name'],
                        $row['tender_file_no'],
                        $row['invoice'],
                        '-',
                        $row['returned_by'] ?: 'N/A',
                        $row['quantity']
                    ]
                ];
            }
            
            // Sort by date descending
            usort($all_records, function($a, $b) {
                return $b['date'] - $a['date'];
            });
            
            foreach ($all_records as $record) {
                $response['records'][] = $record['data'];
            }
            
            $response['total_records'] = count($response['records']);
            $response['summary'] = [
                'receiving_count' => $total_receiving,
                'issuing_count' => $total_issuing,
                'return_count' => $total_returns
            ];
            break;
            
        case 'ribbons':
            // Combined Ribbons Report (Receiving + Issuing + Returns)
            $response['report_title'] = 'Ribbons Management Report';
            $response['report_type_label'] = 'Ribbons Management';
            $response['columns'] = ['Date', 'Type', 'Model', 'LOT', 'Supplier', 'PR No', 'File No', 'Division', 'Receiver Name', 'Quantity'];
            
            $all_records = [];
            $total_receiving = 0;
            $total_issuing = 0;
            $total_returns = 0;
            
            // Get Ribbons Receiving
            $date_filter_ribbons = $date_filter;
            $query = "SELECT receive_date as date, ribbon_model, lot, supplier_name, tender_file_no, invoice,
                     (jct_quantity + uct_quantity) as quantity, 'Receiving' as type
                     FROM ribbons_receiving WHERE 1=1 $date_filter_ribbons $supplier_filter $lot_filter";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND ribbon_model LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total_receiving++;
                $all_records[] = [
                    'date' => strtotime($row['date']),
                    'data' => [
                        date('M d, Y', strtotime($row['date'])),
                        '<span class="badge badge-success">📥 Receiving</span>',
                        $row['ribbon_model'] ?: 'N/A',
                        $row['lot'] ?: 'N/A',
                        $row['supplier_name'] ?: 'N/A',
                        $row['tender_file_no'] ?: 'N/A',
                        $row['invoice'] ?: 'N/A',
                        '-',
                        '-',
                        $row['quantity']
                    ]
                ];
            }
            
            // Get Ribbons Issuing with Receiver Details
            $date_filter_issue = str_replace('receive_date', 'issue_date', $date_filter);
            $query = "SELECT ri.issue_date as date, ri.ribbon_model, ri.lot, ri.division, ri.quantity,
                     ri.receiver_name, ri.receiver_emp_no, rr.supplier_name, rr.tender_file_no, rr.invoice,
                     'Issuing' as type
                     FROM ribbons_issuing ri
                     LEFT JOIN ribbons_receiving rr ON ri.lot = rr.lot AND ri.ribbon_id = rr.ribbon_id
                     WHERE 1=1 $date_filter_issue $lot_filter $division_filter";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND ri.ribbon_model LIKE '%$item_escaped%'";
            }
            if (!empty($is_code)) {
                $is_code_escaped = $conn->real_escape_string($is_code);
                $query .= " AND ri.code LIKE '%$is_code_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total_issuing++;
                $receiver_info = $row['receiver_name'] ?: 'N/A';
                if (!empty($row['receiver_emp_no'])) {
                    $receiver_info .= ' (' . $row['receiver_emp_no'] . ')';
                }
                
                $all_records[] = [
                    'date' => strtotime($row['date']),
                    'data' => [
                        date('M d, Y', strtotime($row['date'])),
                        '<span class="badge badge-primary">📤 Issuing</span>',
                        $row['ribbon_model'] ?: 'N/A',
                        $row['lot'] ?: 'N/A',
                        $row['supplier_name'] ?: 'N/A',
                        $row['tender_file_no'] ?: 'N/A',
                        $row['invoice'] ?: 'N/A',
                        $row['division'] ?: 'N/A',
                        $receiver_info,
                        $row['quantity']
                    ]
                ];
            }
            
            // Get Ribbons Returns with Full Details
            $date_filter_return = str_replace('receive_date', 'return_date', $date_filter);
            $query = "SELECT 
                rr.return_date as date, 
                rr.ribbon_model, 
                rr.lot, 
                COALESCE(NULLIF(rr.supplier_name, ''), rrec.supplier_name, 'N/A') as supplier_name,
                COALESCE(rr.tender_file_no, rrec.tender_file_no, 'N/A') as tender_file_no,
                COALESCE(rr.invoice, rrec.invoice, 'N/A') as invoice,
                rr.returned_by,
                rr.quantity, 
                'Return' as type
            FROM ribbons_return rr
            LEFT JOIN ribbons_receiving rrec ON rr.lot = rrec.lot AND rr.ribbon_id = rrec.ribbon_id
            WHERE 1=1 $date_filter_return $lot_filter";
            if (!empty($supplier)) {
                $supplier_escaped = $conn->real_escape_string($supplier);
                $query .= " AND (rr.supplier_name LIKE '%$supplier_escaped%' OR rrec.supplier_name LIKE '%$supplier_escaped%')";
            }
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND rr.ribbon_model LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total_returns++;
                $all_records[] = [
                    'date' => strtotime($row['date']),
                    'data' => [
                        date('M d, Y', strtotime($row['date'])),
                        '<span class="badge badge-danger">🔙 Return</span>',
                        $row['ribbon_model'] ?: 'N/A',
                        $row['lot'] ?: 'N/A',
                        $row['supplier_name'],
                        $row['tender_file_no'],
                        $row['invoice'],
                        '-',
                        $row['returned_by'] ?: 'N/A',
                        $row['quantity']
                    ]
                ];
            }
            
            // Sort by date descending
            usort($all_records, function($a, $b) {
                return $b['date'] - $a['date'];
            });
            
            foreach ($all_records as $record) {
                $response['records'][] = $record['data'];
            }
            
            $response['total_records'] = count($response['records']);
            $response['summary'] = [
                'receiving_count' => $total_receiving,
                'issuing_count' => $total_issuing,
                'return_count' => $total_returns
            ];
            break;
            
        case 'papers_receiving':
            $response['report_title'] = 'Papers Receiving Report';
            $response['report_type_label'] = 'Papers Receiving';
            $response['columns'] = ['Date', 'Paper Type', 'LOT', 'Supplier', 'JCT Qty', 'UCT Qty', 'Unit Price', 'Invoice'];
            
            // Build query with advanced filters
            $order_replace = str_replace(['date_col', 'quantity_col', 'value_col'], ['receive_date', '(jct_quantity + uct_quantity)', '((jct_quantity + uct_quantity) * unit_price)'], $order_clause);
            
            $query = "SELECT 
                receive_date, 
                paper_type, 
                lot, 
                supplier_name, 
                jct_quantity, 
                uct_quantity, 
                unit_price, 
                invoice
            FROM papers_receiving 
            WHERE 1=1 $date_filter $supplier_filter $lot_filter";
            
            // Add item filter for paper_type
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND paper_type LIKE '%$item_escaped%'";
            }
            
            $query .= $order_replace;
            
            $result = $conn->query($query);
            $total_qty = 0;
            $total_value = 0;
            
            while ($row = $result->fetch_assoc()) {
                $qty = $row['jct_quantity'] + $row['uct_quantity'];
                $total_qty += $qty;
                $total_value += $qty * $row['unit_price'];
                
                $response['records'][] = [
                    date('M d, Y', strtotime($row['receive_date'])),
                    $row['paper_type'],
                    $row['lot'],
                    $row['supplier_name'],
                    $row['jct_quantity'],
                    $row['uct_quantity'],
                    'Rs. ' . number_format($row['unit_price'], 2),
                    $row['invoice'] ?: 'N/A'
                ];
            }
            
            $response['total_records'] = count($response['records']);
            $response['summary'] = [
                'total_quantity' => $total_qty,
                'total_value' => $total_value,
                'receiving_count' => $response['total_records']
            ];
            break;
            
        case 'papers_issuing':
            $response['report_title'] = 'Papers Issuing Report';
            $response['report_type_label'] = 'Papers Issuing';
            $response['columns'] = ['Date', 'Code', 'Paper Type', 'LOT', 'Division', 'Receiver', 'Quantity', 'Remarks'];
            
            $date_filter_issue = str_replace('receive_date', 'issue_date', $date_filter);
            $order_replace = str_replace(['date_col', 'quantity_col', 'value_col'], ['issue_date', 'quantity', 'quantity'], $order_clause);
            
            $query = "SELECT 
                issue_date, 
                code, 
                paper_type, 
                lot, 
                division, 
                receiver_name, 
                quantity, 
                remarks
            FROM papers_issuing 
            WHERE 1=1 $date_filter_issue $lot_filter $division_filter";
            
            // Add item filter for paper_type
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND paper_type LIKE '%$item_escaped%'";
            }
            
            $query .= $order_replace;
            
            $result = $conn->query($query);
            $total_qty = 0;
            
            while ($row = $result->fetch_assoc()) {
                $total_qty += $row['quantity'];
                
                $response['records'][] = [
                    date('M d, Y', strtotime($row['issue_date'])),
                    $row['code'],
                    $row['paper_type'],
                    $row['lot'],
                    $row['division'],
                    $row['receiver_name'],
                    $row['quantity'],
                    $row['remarks'] ?: 'N/A'
                ];
            }
            
            $response['total_records'] = count($response['records']);
            $response['summary'] = [
                'total_quantity' => $total_qty,
                'issuing_count' => $response['total_records']
            ];
            break;
            
        case 'papers_return':
            $response['report_title'] = 'Papers Returns Report';
            $response['report_type_label'] = 'Papers Returns';
            $response['columns'] = ['Date', 'Code', 'Paper Type', 'LOT', 'Supplier', 'Quantity', 'Returned By', 'Reason'];
            
            $date_filter_return = str_replace('receive_date', 'return_date', $date_filter);
            $order_replace = str_replace(['date_col', 'quantity_col', 'value_col'], ['return_date', 'quantity', 'quantity'], $order_clause);
            
            $query = "SELECT 
                return_date, 
                code, 
                paper_type, 
                lot, 
                supplier_name, 
                quantity, 
                return_by,
                reason
            FROM papers_return 
            WHERE 1=1 $date_filter_return $supplier_filter $lot_filter";
            
            // Add item filter for paper_type
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND paper_type LIKE '%$item_escaped%'";
            }
            
            $query .= $order_replace;
            
            $result = $conn->query($query);
            $total_qty = 0;
            
            while ($row = $result->fetch_assoc()) {
                $total_qty += $row['quantity'];
                
                $response['records'][] = [
                    date('M d, Y', strtotime($row['return_date'])),
                    $row['code'] ?: 'N/A',
                    $row['paper_type'] ?: 'N/A',
                    $row['lot'] ?: 'N/A',
                    $row['supplier_name'] ?: 'N/A',
                    $row['quantity'],
                    $row['return_by'] ?: 'N/A',
                    $row['reason'] ? substr($row['reason'], 0, 50) . (strlen($row['reason']) > 50 ? '...' : '') : 'N/A'
                ];
            }
            
            $response['total_records'] = count($response['records']);
            $response['summary'] = [
                'total_quantity' => $total_qty,
                'return_count' => $response['total_records']
            ];
            break;
            
        case 'toner_receiving':
            $response['report_title'] = 'Toner Receiving Report';
            $response['report_type_label'] = 'Toner Receiving';
            $response['columns'] = ['Date', 'Model', 'Color', 'LOT', 'Supplier', 'JCT Qty', 'UCT Qty', 'Unit Price', 'Invoice'];
            
            $order_replace = str_replace(['date_col', 'quantity_col', 'value_col'], ['receive_date', '(jct_quantity + uct_quantity)', '((jct_quantity + uct_quantity) * unit_price)'], $order_clause);
            
            $query = "SELECT 
                receive_date, 
                toner_model, 
                color, 
                lot, 
                supplier_name, 
                jct_quantity,
                uct_quantity, 
                unit_price, 
                invoice
            FROM toner_receiving 
            WHERE 1=1 $date_filter $supplier_filter $lot_filter";
            
            // Add item filter for toner_model
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND toner_model LIKE '%$item_escaped%'";
            }
            
            $query .= $order_replace;
            
            $result = $conn->query($query);
            $total_qty = 0;
            $total_value = 0;
            
            while ($row = $result->fetch_assoc()) {
                $qty = $row['jct_quantity'] + $row['uct_quantity'];
                $total_qty += $qty;
                $total_value += $qty * $row['unit_price'];
                
                $response['records'][] = [
                    date('M d, Y', strtotime($row['receive_date'])),
                    $row['toner_model'],
                    $row['color'],
                    $row['lot'],
                    $row['supplier_name'],
                    $row['jct_quantity'],
                    $row['uct_quantity'],
                    'Rs. ' . number_format($row['unit_price'], 2),
                    $row['invoice'] ?: 'N/A'
                ];
            }
            
            $response['total_records'] = count($response['records']);
            $response['summary'] = [
                'total_quantity' => $total_qty,
                'total_value' => $total_value,
                'receiving_count' => $response['total_records']
            ];
            break;
            
        case 'toner_issuing':
            $response['report_title'] = 'Toner Issuing Report';
            $response['report_type_label'] = 'Toner Issuing';
            $response['columns'] = ['Date', 'Model', 'Code', 'Color', 'LOT', 'Division', 'Printer Model', 'Quantity'];
            
            $date_filter_issue = str_replace('receive_date', 'issue_date', $date_filter);
            $order_replace = str_replace(['date_col', 'quantity_col', 'value_col'], ['issue_date', 'quantity', 'quantity'], $order_clause);
            
            $query = "SELECT 
                issue_date, 
                toner_model, 
                code, 
                color, 
                lot, 
                division, 
                printer_model, 
                quantity
            FROM toner_issuing 
            WHERE 1=1 $date_filter_issue $lot_filter $division_filter";
            
            // Add item filter for toner_model
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND toner_model LIKE '%$item_escaped%'";
            }
            
            // Add IS code filter
            if (!empty($is_code)) {
                $is_code_escaped = $conn->real_escape_string($is_code);
                $query .= " AND code LIKE '%$is_code_escaped%'";
            }
            
            $query .= $order_replace;
            
            $result = $conn->query($query);
            $total_qty = 0;
            
            while ($row = $result->fetch_assoc()) {
                $total_qty += $row['quantity'];
                
                $response['records'][] = [
                    date('M d, Y', strtotime($row['issue_date'])),
                    $row['toner_model'],
                    $row['code'],
                    $row['color'],
                    $row['lot'],
                    $row['division'],
                    $row['printer_model'],
                    $row['quantity']
                ];
            }
            
            $response['total_records'] = count($response['records']);
            $response['summary'] = [
                'total_quantity' => $total_qty,
                'issuing_count' => $response['total_records']
            ];
            break;
            
        case 'toner_return':
            $response['report_title'] = 'Toner Returns Report';
            $response['report_type_label'] = 'Toner Returns';
            $response['columns'] = ['Return Date', 'Model', 'Code', 'LOT', 'Supplier', 'PR No', 'File No', 'Invoice', 'Issue Date', 'Division', 'Receiver Name', 'Quantity', 'Returned By', 'Reason'];
            
            $date_filter_return = str_replace('receive_date', 'return_date', $date_filter);
            $order_replace = str_replace(['date_col', 'quantity_col', 'value_col'], ['tr.return_date', 'tr.quantity', 'tr.quantity'], $order_clause);
            
            $query = "SELECT 
                tr.return_date, 
                tr.toner_model, 
                tr.code, 
                tr.lot, 
                tr.toner_id,
                COALESCE(NULLIF(trec.supplier_name, ''), NULLIF(tr.supplier_name, ''), 'N/A') as supplier_name,
                COALESCE(
                    NULLIF(trec.pr_no, ''),
                    (SELECT pr_no FROM toner_receiving 
                     WHERE toner_model LIKE CONCAT('%', tr.toner_model, '%')
                     AND pr_no != '' 
                     AND pr_no IS NOT NULL 
                     ORDER BY receive_date DESC 
                     LIMIT 1),
                    'Not Available'
                ) as pr_no,
                COALESCE(
                    NULLIF(trec.tender_file_no, ''), 
                    NULLIF(tr.tender_file_no, ''),
                    (SELECT tender_file_no FROM toner_receiving 
                     WHERE toner_model LIKE CONCAT('%', tr.toner_model, '%')
                     AND tender_file_no != '' 
                     AND tender_file_no IS NOT NULL 
                     AND tender_file_no != 'Tender File Number' 
                     ORDER BY receive_date DESC 
                     LIMIT 1),
                    'Not Available'
                ) as tender_file_no,
                COALESCE(
                    NULLIF(trec.invoice, ''),
                    NULLIF(tr.invoice, ''),
                    (SELECT invoice FROM toner_receiving 
                     WHERE toner_model LIKE CONCAT('%', tr.toner_model, '%')
                     AND invoice != '' 
                     AND invoice IS NOT NULL 
                     AND invoice != 'Invoice' 
                     ORDER BY receive_date DESC 
                     LIMIT 1),
                    'Not Available'
                ) as invoice,
                COALESCE(trec.jct_quantity, 0) as jct_quantity,
                COALESCE(trec.uct_quantity, 0) as uct_quantity,
                COALESCE(trec.unit_price, 0) as unit_price,
                ti.issue_date,
                ti.division,
                ti.receiver_name,
                ti.receiver_emp_no,
                tr.quantity, 
                tr.returned_by,
                tr.reason
            FROM toner_return tr
            LEFT JOIN toner_issuing ti ON ti.code = tr.code AND ti.toner_id = tr.toner_id
            LEFT JOIN toner_receiving trec ON trec.lot = tr.lot AND trec.toner_id = tr.toner_id
            WHERE 1=1 $date_filter_return $lot_filter";
            
            // Add supplier filter
            if (!empty($supplier)) {
                $supplier_escaped = $conn->real_escape_string($supplier);
                $query .= " AND (trec.supplier_name LIKE '%$supplier_escaped%' OR tr.supplier_name LIKE '%$supplier_escaped%')";
            }
            
            // Add item filter for toner_model
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND tr.toner_model LIKE '%$item_escaped%'";
            }
            
            // Add IS code filter
            if (!empty($is_code)) {
                $is_code_escaped = $conn->real_escape_string($is_code);
                $query .= " AND tr.code LIKE '%$is_code_escaped%'";
            }
            
            // Add division filter
            if (!empty($division)) {
                $division_escaped = $conn->real_escape_string($division);
                $query .= " AND ti.division = '$division_escaped'";
            }
            
            $query .= $order_replace;
            
            $result = $conn->query($query);
            $total_qty = 0;
            
            while ($row = $result->fetch_assoc()) {
                $total_qty += $row['quantity'];
                
                $receiver_info = $row['receiver_name'] ?: 'N/A';
                if (!empty($row['receiver_emp_no'])) {
                    $receiver_info .= ' (' . $row['receiver_emp_no'] . ')';
                }
                
                $total_receiving_qty = $row['jct_quantity'] + $row['uct_quantity'];
                $full_payment = $total_receiving_qty * $row['unit_price'];
                
                $response['records'][] = [
                    date('M d, Y', strtotime($row['return_date'])),
                    $row['toner_model'] ?: 'N/A',
                    $row['code'] ?: 'N/A',
                    $row['lot'] ?: 'N/A',
                    $row['supplier_name'],
                    $row['pr_no'],
                    $row['tender_file_no'],
                    $row['invoice'],
                    $row['issue_date'] ? date('M d, Y', strtotime($row['issue_date'])) : 'N/A',
                    $row['division'] ?: 'N/A',
                    $receiver_info,
                    $row['quantity'],
                    $row['returned_by'] ?: 'N/A',
                    $row['reason'] ? substr($row['reason'], 0, 50) . (strlen($row['reason']) > 50 ? '...' : '') : 'N/A',
                    $row['jct_quantity'],
                    $row['uct_quantity'],
                    $total_receiving_qty,
                    $row['unit_price'],
                    $full_payment
                ];
            }
            
            $response['total_records'] = count($response['records']);
            $response['summary'] = [
                'total_quantity' => $total_qty,
                'return_count' => $response['total_records']
            ];
            break;
            
        case 'ribbons_receiving':
            $response['report_title'] = 'Ribbons Receiving Report';
            $response['report_type_label'] = 'Ribbons Receiving';
            $response['columns'] = ['Date', 'Model', 'LOT', 'Supplier', 'JCT Qty', 'UCT Qty', 'Unit Price', 'Invoice'];
            
            $order_replace = str_replace(['date_col', 'quantity_col', 'value_col'], ['receive_date', '(jct_quantity + uct_quantity)', '((jct_quantity + uct_quantity) * unit_price)'], $order_clause);
            
            $query = "SELECT 
                receive_date, 
                ribbon_model, 
                lot, 
                supplier_name, 
                jct_quantity,
                uct_quantity, 
                unit_price, 
                invoice
            FROM ribbons_receiving 
            WHERE 1=1 $date_filter $supplier_filter $lot_filter";
            
            // Add item filter for ribbon_model
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND ribbon_model LIKE '%$item_escaped%'";
            }
            
            $query .= $order_replace;
            
            $result = $conn->query($query);
            $total_qty = 0;
            $total_value = 0;
            
            while ($row = $result->fetch_assoc()) {
                $qty = $row['jct_quantity'] + $row['uct_quantity'];
                $total_qty += $qty;
                $total_value += $qty * $row['unit_price'];
                
                $response['records'][] = [
                    date('M d, Y', strtotime($row['receive_date'])),
                    $row['ribbon_model'],
                    $row['lot'],
                    $row['supplier_name'],
                    $row['jct_quantity'],
                    $row['uct_quantity'],
                    'Rs. ' . number_format($row['unit_price'], 2),
                    $row['invoice'] ?: 'N/A'
                ];
            }
            
            $response['total_records'] = count($response['records']);
            $response['summary'] = [
                'total_quantity' => $total_qty,
                'total_value' => $total_value,
                'receiving_count' => $response['total_records']
            ];
            break;
            
        case 'ribbons_issuing':
            $response['report_title'] = 'Ribbons Issuing Report';
            $response['report_type_label'] = 'Ribbons Issuing';
            $response['columns'] = ['Date', 'Model', 'Code', 'LOT', 'Division', 'Quantity'];
            
            $date_filter_issue = str_replace('receive_date', 'issue_date', $date_filter);
            $order_replace = str_replace(['date_col', 'quantity_col', 'value_col'], ['issue_date', 'quantity', 'quantity'], $order_clause);
            
            $query = "SELECT 
                issue_date, 
                ribbon_model, 
                code, 
                lot, 
                division, 
                quantity
            FROM ribbons_issuing 
            WHERE 1=1 $date_filter_issue $lot_filter $division_filter";
            
            // Add item filter for ribbon_model
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND ribbon_model LIKE '%$item_escaped%'";
            }
            
            // Add IS code filter
            if (!empty($is_code)) {
                $is_code_escaped = $conn->real_escape_string($is_code);
                $query .= " AND code LIKE '%$is_code_escaped%'";
            }
            
            $query .= $order_replace;
            
            $result = $conn->query($query);
            $total_qty = 0;
            
            while ($row = $result->fetch_assoc()) {
                $total_qty += $row['quantity'];
                
                $response['records'][] = [
                    date('M d, Y', strtotime($row['issue_date'])),
                    $row['ribbon_model'],
                    $row['code'],
                    $row['lot'],
                    $row['division'],
                    $row['quantity']
                ];
            }
            
            $response['total_records'] = count($response['records']);
            $response['summary'] = [
                'total_quantity' => $total_qty,
                'issuing_count' => $response['total_records']
            ];
            break;
            
        case 'ribbons_return':
            $response['report_title'] = 'Ribbons Returns Report';
            $response['report_type_label'] = 'Ribbons Returns';
            $response['columns'] = ['Date', 'Model', 'Code', 'LOT', 'Supplier', 'Quantity', 'Returned By', 'Reason'];
            
            $date_filter_return = str_replace('receive_date', 'return_date', $date_filter);
            $order_replace = str_replace(['date_col', 'quantity_col', 'value_col'], ['return_date', 'quantity', 'quantity'], $order_clause);
            
            $query = "SELECT 
                return_date, 
                ribbon_model, 
                code, 
                lot, 
                supplier_name, 
                quantity, 
                returned_by,
                reason
            FROM ribbons_return 
            WHERE 1=1 $date_filter_return $supplier_filter $lot_filter";
            
            // Add item filter for ribbon_model
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND ribbon_model LIKE '%$item_escaped%'";
            }
            
            $query .= $order_replace;
            
            $result = $conn->query($query);
            $total_qty = 0;
            
            while ($row = $result->fetch_assoc()) {
                $total_qty += $row['quantity'];
                
                $response['records'][] = [
                    date('M d, Y', strtotime($row['return_date'])),
                    $row['ribbon_model'] ?: 'N/A',
                    $row['code'] ?: 'N/A',
                    $row['lot'] ?: 'N/A',
                    $row['supplier_name'] ?: 'N/A',
                    $row['quantity'],
                    $row['returned_by'] ?: 'N/A',
                    $row['reason'] ? substr($row['reason'], 0, 50) . (strlen($row['reason']) > 50 ? '...' : '') : 'N/A'
                ];
            }
            
            $response['total_records'] = count($response['records']);
            $response['summary'] = [
                'total_quantity' => $total_qty,
                'return_count' => $response['total_records']
            ];
            break;
            
        default:
            // All Inventory - Combined report from papers_receiving, toner_receiving, ribbons_receiving
            $response['report_title'] = 'Comprehensive Inventory Report';
            $response['report_type_label'] = 'All Inventory';
            $response['columns'] = ['Date', 'Category', 'Item', 'Supplier', 'Quantity', 'Unit Price', 'Total Value'];
            
            $all_records = [];
            $total_qty = 0;
            $total_value = 0;
            
            // Get Papers Receiving
            $query = "SELECT receive_date, paper_type, supplier_name, (jct_quantity + uct_quantity) as quantity, unit_price 
                     FROM papers_receiving WHERE 1=1 $date_filter $supplier_filter";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND paper_type LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $qty = $row['quantity'];
                $value = $qty * $row['unit_price'];
                $total_qty += $qty;
                $total_value += $value;
                $all_records[] = [
                    'date' => strtotime($row['receive_date']),
                    'data' => [
                        date('M d, Y', strtotime($row['receive_date'])),
                        'Papers',
                        $row['paper_type'],
                        $row['supplier_name'],
                        $qty,
                        'Rs. ' . number_format($row['unit_price'], 2),
                        'Rs. ' . number_format($value, 2)
                    ]
                ];
            }
            
            // Get Toner Receiving
            $query = "SELECT receive_date, toner_model, supplier_name, (jct_quantity + uct_quantity) as quantity, unit_price 
                     FROM toner_receiving WHERE 1=1 $date_filter $supplier_filter";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND toner_model LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $qty = $row['quantity'];
                $value = $qty * $row['unit_price'];
                $total_qty += $qty;
                $total_value += $value;
                $all_records[] = [
                    'date' => strtotime($row['receive_date']),
                    'data' => [
                        date('M d, Y', strtotime($row['receive_date'])),
                        'Toner',
                        $row['toner_model'],
                        $row['supplier_name'],
                        $qty,
                        'Rs. ' . number_format($row['unit_price'], 2),
                        'Rs. ' . number_format($value, 2)
                    ]
                ];
            }
            
            // Get Ribbons Receiving
            $query = "SELECT receive_date, ribbon_model, supplier_name, (jct_quantity + uct_quantity) as quantity, unit_price 
                     FROM ribbons_receiving WHERE 1=1 $date_filter $supplier_filter";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND ribbon_model LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $qty = $row['quantity'];
                $value = $qty * $row['unit_price'];
                $total_qty += $qty;
                $total_value += $value;
                $all_records[] = [
                    'date' => strtotime($row['receive_date']),
                    'data' => [
                        date('M d, Y', strtotime($row['receive_date'])),
                        'Ribbons',
                        $row['ribbon_model'],
                        $row['supplier_name'],
                        $qty,
                        'Rs. ' . number_format($row['unit_price'], 2),
                        'Rs. ' . number_format($value, 2)
                    ]
                ];
            }
            
            // Sort by date descending
            usort($all_records, function($a, $b) {
                return $b['date'] - $a['date'];
            });
            
            // Extract just the data arrays
            foreach ($all_records as $record) {
                $response['records'][] = $record['data'];
            }
            
            $response['total_records'] = count($response['records']);
            $response['summary'] = [
                'total_quantity' => $total_qty,
                'total_value' => $total_value,
                'receiving_count' => $response['total_records']
            ];
            break;
            
        case 'stock_summary':
            // Current Stock Summary Report
            $response['report_title'] = 'Current Stock Summary';
            $response['report_type_label'] = 'Stock Summary';
            $response['columns'] = ['Category', 'Item/Model', 'JCT Stock', 'UCT Stock', 'Total Stock', 'Unit Price', 'Total Value'];
            
            $all_items = [];
            $total_items = 0;
            $total_value = 0;
            
            // Papers Stock
            $query = "SELECT paper_type, jct_stock, uct_stock, unit_price FROM papers_master WHERE 1=1";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND paper_type LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total = $row['jct_stock'] + $row['uct_stock'];
                $value = $total * $row['unit_price'];
                $total_items += $total;
                $total_value += $value;
                $all_items[] = [
                    '<span class="badge badge-info">Papers</span>',
                    $row['paper_type'],
                    $row['jct_stock'],
                    $row['uct_stock'],
                    $total,
                    'Rs. ' . number_format($row['unit_price'], 2),
                    'Rs. ' . number_format($value, 2)
                ];
            }
            
            // Toner Stock
            $query = "SELECT toner_model, jct_stock, uct_stock, unit_price FROM toner_master WHERE 1=1";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND toner_model LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total = $row['jct_stock'] + $row['uct_stock'];
                $value = $total * $row['unit_price'];
                $total_items += $total;
                $total_value += $value;
                $all_items[] = [
                    '<span class="badge badge-warning">Toner</span>',
                    $row['toner_model'],
                    $row['jct_stock'],
                    $row['uct_stock'],
                    $total,
                    'Rs. ' . number_format($row['unit_price'], 2),
                    'Rs. ' . number_format($value, 2)
                ];
            }
            
            // Ribbons Stock
            $query = "SELECT ribbon_model, jct_stock, uct_stock, unit_price FROM ribbons_master WHERE 1=1";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND ribbon_model LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total = $row['jct_stock'] + $row['uct_stock'];
                $value = $total * $row['unit_price'];
                $total_items += $total;
                $total_value += $value;
                $all_items[] = [
                    '<span class="badge badge-success">Ribbons</span>',
                    $row['ribbon_model'],
                    $row['jct_stock'],
                    $row['uct_stock'],
                    $total,
                    'Rs. ' . number_format($row['unit_price'], 2),
                    'Rs. ' . number_format($value, 2)
                ];
            }
            
            $response['records'] = $all_items;
            $response['total_records'] = count($all_items);
            $response['summary'] = [
                'total_quantity' => $total_items,
                'total_value' => $total_value
            ];
            break;
            
        case 'low_stock':
            // Low Stock Alert Report
            $response['report_title'] = 'Low Stock Alert';
            $response['report_type_label'] = 'Low Stock Items';
            $response['columns'] = ['Category', 'Item/Model', 'JCT Stock', 'UCT Stock', 'Total Stock', 'Status'];
            
            $low_stock_threshold = 10; // Define threshold
            $all_items = [];
            
            // Papers Low Stock
            $query = "SELECT paper_type, jct_stock, uct_stock FROM papers_master WHERE (jct_stock + uct_stock) <= $low_stock_threshold";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND paper_type LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total = $row['jct_stock'] + $row['uct_stock'];
                $status = $total == 0 ? '<span class="badge badge-danger">Out of Stock</span>' : '<span class="badge badge-warning">Low Stock</span>';
                $all_items[] = [
                    '<span class="badge badge-info">Papers</span>',
                    $row['paper_type'],
                    $row['jct_stock'],
                    $row['uct_stock'],
                    $total,
                    $status
                ];
            }
            
            // Toner Low Stock
            $query = "SELECT toner_model, jct_stock, uct_stock FROM toner_master WHERE (jct_stock + uct_stock) <= $low_stock_threshold";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND toner_model LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total = $row['jct_stock'] + $row['uct_stock'];
                $status = $total == 0 ? '<span class="badge badge-danger">Out of Stock</span>' : '<span class="badge badge-warning">Low Stock</span>';
                $all_items[] = [
                    '<span class="badge badge-warning">Toner</span>',
                    $row['toner_model'],
                    $row['jct_stock'],
                    $row['uct_stock'],
                    $total,
                    $status
                ];
            }
            
            // Ribbons Low Stock
            $query = "SELECT ribbon_model, jct_stock, uct_stock FROM ribbons_master WHERE (jct_stock + uct_stock) <= $low_stock_threshold";
            if (!empty($item)) {
                $item_escaped = $conn->real_escape_string($item);
                $query .= " AND ribbon_model LIKE '%$item_escaped%'";
            }
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total = $row['jct_stock'] + $row['uct_stock'];
                $status = $total == 0 ? '<span class="badge badge-danger">Out of Stock</span>' : '<span class="badge badge-warning">Low Stock</span>';
                $all_items[] = [
                    '<span class="badge badge-success">Ribbons</span>',
                    $row['ribbon_model'],
                    $row['jct_stock'],
                    $row['uct_stock'],
                    $total,
                    $status
                ];
            }
            
            $response['records'] = $all_items;
            $response['total_records'] = count($all_items);
            $response['summary'] = [
                'total_quantity' => count($all_items)
            ];
            break;
            
        case 'stock_movement':
            // Stock Movement Analysis
            $response['report_title'] = 'Stock Movement Analysis';
            $response['report_type_label'] = 'Stock Movement';
            $response['columns'] = ['Item/Model', 'Category', 'Total Received', 'Total Issued', 'Total Returned', 'Current Stock', 'Net Movement'];
            
            $movements = [];
            
            // Papers Movement
            if (!empty($item)) {
                $item_filter = " AND paper_type LIKE '%" . $conn->real_escape_string($item) . "%'";
            } else {
                $item_filter = "";
            }
            
            $query = "SELECT 
                pm.paper_type,
                COALESCE(SUM(pr.jct_quantity + pr.uct_quantity), 0) as total_received,
                COALESCE((SELECT SUM(quantity) FROM papers_issuing WHERE paper_type = pm.paper_type $date_filter), 0) as total_issued,
                COALESCE((SELECT SUM(quantity) FROM papers_return WHERE paper_type = pm.paper_type $date_filter), 0) as total_returned,
                (pm.jct_stock + pm.uct_stock) as current_stock
            FROM papers_master pm
            LEFT JOIN papers_receiving pr ON pm.paper_type = pr.paper_type $date_filter
            WHERE 1=1 $item_filter
            GROUP BY pm.paper_type";
            
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $net = $row['total_received'] - $row['total_issued'] + $row['total_returned'];
                $movements[] = [
                    $row['paper_type'],
                    '<span class="badge badge-info">Papers</span>',
                    $row['total_received'],
                    $row['total_issued'],
                    $row['total_returned'],
                    $row['current_stock'],
                    $net
                ];
            }
            
            // Toner Movement
            if (!empty($item)) {
                $item_filter = " AND toner_model LIKE '%" . $conn->real_escape_string($item) . "%'";
            } else {
                $item_filter = "";
            }
            
            $query = "SELECT 
                tm.toner_model,
                COALESCE(SUM(tr.jct_quantity + tr.uct_quantity), 0) as total_received,
                COALESCE((SELECT SUM(quantity) FROM toner_issuing WHERE toner_model = tm.toner_model $date_filter), 0) as total_issued,
                COALESCE((SELECT SUM(quantity) FROM toner_return WHERE toner_model = tm.toner_model $date_filter), 0) as total_returned,
                (tm.jct_stock + tm.uct_stock) as current_stock
            FROM toner_master tm
            LEFT JOIN toner_receiving tr ON tm.toner_model = tr.toner_model $date_filter
            WHERE 1=1 $item_filter
            GROUP BY tm.toner_model";
            
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $net = $row['total_received'] - $row['total_issued'] + $row['total_returned'];
                $movements[] = [
                    $row['toner_model'],
                    '<span class="badge badge-warning">Toner</span>',
                    $row['total_received'],
                    $row['total_issued'],
                    $row['total_returned'],
                    $row['current_stock'],
                    $net
                ];
            }
            
            // Ribbons Movement
            if (!empty($item)) {
                $item_filter = " AND ribbon_model LIKE '%" . $conn->real_escape_string($item) . "%'";
            } else {
                $item_filter = "";
            }
            
            $query = "SELECT 
                rm.ribbon_model,
                COALESCE(SUM(rr.jct_quantity + rr.uct_quantity), 0) as total_received,
                COALESCE((SELECT SUM(quantity) FROM ribbons_issuing WHERE ribbon_model = rm.ribbon_model $date_filter), 0) as total_issued,
                COALESCE((SELECT SUM(quantity) FROM ribbons_return WHERE ribbon_model = rm.ribbon_model $date_filter), 0) as total_returned,
                (rm.jct_stock + rm.uct_stock) as current_stock
            FROM ribbons_master rm
            LEFT JOIN ribbons_receiving rr ON rm.ribbon_model = rr.ribbon_model $date_filter
            WHERE 1=1 $item_filter
            GROUP BY rm.ribbon_model";
            
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $net = $row['total_received'] - $row['total_issued'] + $row['total_returned'];
                $movements[] = [
                    $row['ribbon_model'],
                    '<span class="badge badge-success">Ribbons</span>',
                    $row['total_received'],
                    $row['total_issued'],
                    $row['total_returned'],
                    $row['current_stock'],
                    $net
                ];
            }
            
            $response['records'] = $movements;
            $response['total_records'] = count($movements);
            break;
            
        case 'value_summary':
            // Inventory Value Summary
            $response['report_title'] = 'Inventory Value Summary';
            $response['report_type_label'] = 'Value Summary';
            $response['columns'] = ['Category', 'Total Items', 'Total Quantity', 'Average Unit Price', 'Total Value'];
            
            $summary_data = [];
            $grand_total_value = 0;
            
            // Papers Value
            $query = "SELECT 
                COUNT(*) as item_count,
                SUM(jct_stock + uct_stock) as total_qty,
                AVG(unit_price) as avg_price,
                SUM((jct_stock + uct_stock) * unit_price) as total_value
            FROM papers_master";
            $result = $conn->query($query);
            if ($row = $result->fetch_assoc()) {
                $grand_total_value += $row['total_value'];
                $summary_data[] = [
                    '<span class="badge badge-info">Papers</span>',
                    $row['item_count'],
                    $row['total_qty'] ?: 0,
                    'Rs. ' . number_format($row['avg_price'] ?: 0, 2),
                    'Rs. ' . number_format($row['total_value'] ?: 0, 2)
                ];
            }
            
            // Toner Value
            $query = "SELECT 
                COUNT(*) as item_count,
                SUM(jct_stock + uct_stock) as total_qty,
                AVG(unit_price) as avg_price,
                SUM((jct_stock + uct_stock) * unit_price) as total_value
            FROM toner_master";
            $result = $conn->query($query);
            if ($row = $result->fetch_assoc()) {
                $grand_total_value += $row['total_value'];
                $summary_data[] = [
                    '<span class="badge badge-warning">Toner</span>',
                    $row['item_count'],
                    $row['total_qty'] ?: 0,
                    'Rs. ' . number_format($row['avg_price'] ?: 0, 2),
                    'Rs. ' . number_format($row['total_value'] ?: 0, 2)
                ];
            }
            
            // Ribbons Value
            $query = "SELECT 
                COUNT(*) as item_count,
                SUM(jct_stock + uct_stock) as total_qty,
                AVG(unit_price) as avg_price,
                SUM((jct_stock + uct_stock) * unit_price) as total_value
            FROM ribbons_master";
            $result = $conn->query($query);
            if ($row = $result->fetch_assoc()) {
                $grand_total_value += $row['total_value'];
                $summary_data[] = [
                    '<span class="badge badge-success">Ribbons</span>',
                    $row['item_count'],
                    $row['total_qty'] ?: 0,
                    'Rs. ' . number_format($row['avg_price'] ?: 0, 2),
                    'Rs. ' . number_format($row['total_value'] ?: 0, 2)
                ];
            }
            
            $response['records'] = $summary_data;
            $response['total_records'] = count($summary_data);
            $response['summary'] = [
                'total_value' => $grand_total_value
            ];
            break;
            
        case 'supplier_spending':
            // Supplier Spending Analysis
            $response['report_title'] = 'Supplier Spending Analysis';
            $response['report_type_label'] = 'Supplier Spending';
            $response['columns'] = ['Supplier Name', 'Category', 'Total Orders', 'Total Quantity', 'Total Spending'];
            
            $supplier_data = [];
            $total_spending = 0;
            
            // Papers Suppliers
            $query = "SELECT 
                supplier_name,
                COUNT(*) as order_count,
                SUM(jct_quantity + uct_quantity) as total_qty,
                SUM((jct_quantity + uct_quantity) * unit_price) as total_spent
            FROM papers_receiving
            WHERE 1=1 $date_filter $supplier_filter
            GROUP BY supplier_name
            ORDER BY total_spent DESC";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total_spending += $row['total_spent'];
                $supplier_data[] = [
                    $row['supplier_name'],
                    '<span class="badge badge-info">Papers</span>',
                    $row['order_count'],
                    $row['total_qty'],
                    'Rs. ' . number_format($row['total_spent'], 2)
                ];
            }
            
            // Toner Suppliers
            $query = "SELECT 
                supplier_name,
                COUNT(*) as order_count,
                SUM(jct_quantity + uct_quantity) as total_qty,
                SUM((jct_quantity + uct_quantity) * unit_price) as total_spent
            FROM toner_receiving
            WHERE 1=1 $date_filter $supplier_filter
            GROUP BY supplier_name
            ORDER BY total_spent DESC";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total_spending += $row['total_spent'];
                $supplier_data[] = [
                    $row['supplier_name'],
                    '<span class="badge badge-warning">Toner</span>',
                    $row['order_count'],
                    $row['total_qty'],
                    'Rs. ' . number_format($row['total_spent'], 2)
                ];
            }
            
            // Ribbons Suppliers
            $query = "SELECT 
                supplier_name,
                COUNT(*) as order_count,
                SUM(jct_quantity + uct_quantity) as total_qty,
                SUM((jct_quantity + uct_quantity) * unit_price) as total_spent
            FROM ribbons_receiving
            WHERE 1=1 $date_filter $supplier_filter
            GROUP BY supplier_name
            ORDER BY total_spent DESC";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $total_spending += $row['total_spent'];
                $supplier_data[] = [
                    $row['supplier_name'],
                    '<span class="badge badge-success">Ribbons</span>',
                    $row['order_count'],
                    $row['total_qty'],
                    'Rs. ' . number_format($row['total_spent'], 2)
                ];
            }
            
            $response['records'] = $supplier_data;
            $response['total_records'] = count($supplier_data);
            $response['summary'] = [
                'total_value' => $total_spending
            ];
            break;
            
        case 'division_usage':
            // Division-wise Usage Report
            $response['report_title'] = 'Division-wise Usage Report';
            $response['report_type_label'] = 'Division Usage';
            $response['columns'] = ['Division', 'Category', 'Total Issues', 'Total Quantity'];
            
            $division_data = [];
            
            // Papers by Division
            $date_filter_issue = str_replace('receive_date', 'issue_date', $date_filter);
            $query = "SELECT 
                division,
                COUNT(*) as issue_count,
                SUM(quantity) as total_qty
            FROM papers_issuing
            WHERE 1=1 $date_filter_issue $division_filter
            GROUP BY division
            ORDER BY total_qty DESC";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $division_data[] = [
                    $row['division'],
                    '<span class="badge badge-info">Papers</span>',
                    $row['issue_count'],
                    $row['total_qty']
                ];
            }
            
            // Toner by Division
            $query = "SELECT 
                division,
                COUNT(*) as issue_count,
                SUM(quantity) as total_qty
            FROM toner_issuing
            WHERE 1=1 $date_filter_issue $division_filter
            GROUP BY division
            ORDER BY total_qty DESC";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $division_data[] = [
                    $row['division'],
                    '<span class="badge badge-warning">Toner</span>',
                    $row['issue_count'],
                    $row['total_qty']
                ];
            }
            
            // Ribbons by Division
            $query = "SELECT 
                division,
                COUNT(*) as issue_count,
                SUM(quantity) as total_qty
            FROM ribbons_issuing
            WHERE 1=1 $date_filter_issue $division_filter
            GROUP BY division
            ORDER BY total_qty DESC";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $division_data[] = [
                    $row['division'],
                    '<span class="badge badge-success">Ribbons</span>',
                    $row['issue_count'],
                    $row['total_qty']
                ];
            }
            
            $response['records'] = $division_data;
            $response['total_records'] = count($division_data);
            break;
            
        case 'monthly_trends':
            // Monthly Trends Report
            $response['report_title'] = 'Monthly Trends Report';
            $response['report_type_label'] = 'Monthly Trends';
            $response['columns'] = ['Month', 'Category', 'Receiving', 'Issuing', 'Returns'];
            
            $trends = [];
            $year_filter = $year ?: date('Y');
            
            // Get monthly data for Papers
            for ($m = 1; $m <= 12; $m++) {
                $month_name = date('F', mktime(0, 0, 0, $m, 1));
                
                // Papers
                $rec_query = "SELECT COUNT(*) as cnt FROM papers_receiving WHERE YEAR(receive_date) = $year_filter AND MONTH(receive_date) = $m";
                $iss_query = "SELECT COUNT(*) as cnt FROM papers_issuing WHERE YEAR(issue_date) = $year_filter AND MONTH(issue_date) = $m";
                $ret_query = "SELECT COUNT(*) as cnt FROM papers_return WHERE YEAR(return_date) = $year_filter AND MONTH(return_date) = $m";
                
                $rec = $conn->query($rec_query)->fetch_assoc()['cnt'];
                $iss = $conn->query($iss_query)->fetch_assoc()['cnt'];
                $ret = $conn->query($ret_query)->fetch_assoc()['cnt'];
                
                if ($rec > 0 || $iss > 0 || $ret > 0) {
                    $trends[] = [
                        $month_name . ' ' . $year_filter,
                        '<span class="badge badge-info">Papers</span>',
                        $rec,
                        $iss,
                        $ret
                    ];
                }
            }
            
            $response['records'] = $trends;
            $response['total_records'] = count($trends);
            break;
            
        case 'return_analysis':
            // Return Analysis Report
            $response['report_title'] = 'Return Analysis Report';
            $response['report_type_label'] = 'Return Analysis';
            $response['columns'] = ['Category', 'Item', 'Total Returns', 'Total Quantity', 'Most Common Reason'];
            
            $return_data = [];
            
            // Papers Returns
            $date_filter_return = str_replace('receive_date', 'return_date', $date_filter);
            $query = "SELECT 
                paper_type,
                COUNT(*) as return_count,
                SUM(quantity) as total_qty,
                reason
            FROM papers_return
            WHERE 1=1 $date_filter_return
            GROUP BY paper_type
            ORDER BY return_count DESC";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $return_data[] = [
                    '<span class="badge badge-info">Papers</span>',
                    $row['paper_type'],
                    $row['return_count'],
                    $row['total_qty'],
                    substr($row['reason'], 0, 40) . '...'
                ];
            }
            
            // Toner Returns
            $query = "SELECT 
                toner_model,
                COUNT(*) as return_count,
                SUM(quantity) as total_qty,
                reason
            FROM toner_return
            WHERE 1=1 $date_filter_return
            GROUP BY toner_model
            ORDER BY return_count DESC";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $return_data[] = [
                    '<span class="badge badge-warning">Toner</span>',
                    $row['toner_model'],
                    $row['return_count'],
                    $row['total_qty'],
                    substr($row['reason'], 0, 40) . '...'
                ];
            }
            
            // Ribbons Returns
            $query = "SELECT 
                ribbon_model,
                COUNT(*) as return_count,
                SUM(quantity) as total_qty,
                reason
            FROM ribbons_return
            WHERE 1=1 $date_filter_return
            GROUP BY ribbon_model
            ORDER BY return_count DESC";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $return_data[] = [
                    '<span class="badge badge-success">Ribbons</span>',
                    $row['ribbon_model'],
                    $row['return_count'],
                    $row['total_qty'],
                    substr($row['reason'], 0, 40) . '...'
                ];
            }
            
            $response['records'] = $return_data;
            $response['total_records'] = count($return_data);
            break;
            
        case 'cost_analysis':
            // Cost Analysis Report
            $response['report_title'] = 'Cost Analysis Report';
            $response['report_type_label'] = 'Cost Analysis';
            $response['columns'] = ['Item/Model', 'Category', 'Total Received', 'Avg Unit Price', 'Total Cost', 'Total Issued', 'Cost per Issue'];
            
            $cost_data = [];
            
            // Papers Cost
            $query = "SELECT 
                pm.paper_type,
                SUM(pr.jct_quantity + pr.uct_quantity) as total_received,
                AVG(pr.unit_price) as avg_price,
                SUM((pr.jct_quantity + pr.uct_quantity) * pr.unit_price) as total_cost,
                (SELECT SUM(quantity) FROM papers_issuing WHERE paper_type = pm.paper_type $date_filter) as total_issued
            FROM papers_master pm
            LEFT JOIN papers_receiving pr ON pm.paper_type = pr.paper_type $date_filter
            WHERE 1=1
            GROUP BY pm.paper_type";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $cost_per_issue = ($row['total_issued'] > 0) ? $row['total_cost'] / $row['total_issued'] : 0;
                $cost_data[] = [
                    $row['paper_type'],
                    '<span class="badge badge-info">Papers</span>',
                    $row['total_received'] ?: 0,
                    'Rs. ' . number_format($row['avg_price'] ?: 0, 2),
                    'Rs. ' . number_format($row['total_cost'] ?: 0, 2),
                    $row['total_issued'] ?: 0,
                    'Rs. ' . number_format($cost_per_issue, 2)
                ];
            }
            
            $response['records'] = $cost_data;
            $response['total_records'] = count($cost_data);
            break;
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
