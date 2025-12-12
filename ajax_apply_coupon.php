<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false, 'message' => 'Invalid request.'];

if (isset($input['coupon_code']) && isset($input['doctor_id'])) {
    $code = $input['coupon_code'];
    $doctor_id = $input['doctor_id'];

    // 1. Get the discount code info
    $sql_code = "SELECT discount_percentage FROM discount_codes WHERE code = ? AND doctor_id = ? AND is_active = 1";
    
    if ($stmt_code = $conn->prepare($sql_code)) {
        $stmt_code->bind_param("si", $code, $doctor_id);
        $stmt_code->execute();
        $result_code = $stmt_code->get_result();
        
        if ($result_code->num_rows == 1) {
            $discount = $result_code->fetch_assoc();
            
            // 2. Get the doctor's fee
            $sql_fee = "SELECT fees FROM doctors WHERE user_id = ?";
            if ($stmt_fee = $conn->prepare($sql_fee)) {
                $stmt_fee->bind_param("i", $doctor_id);
                $stmt_fee->execute();
                $result_fee = $stmt_fee->get_result();
                $doctor = $result_fee->fetch_assoc();

                // 3. Calculate final price
                $base_fee = $doctor['fees'];
                $discount_amount = ($base_fee * $discount['discount_percentage']) / 100;
                $final_price = $base_fee - $discount_amount;
                
                $response = [
                    'success' => true,
                    'message' => 'Code applied successfully!',
                    'base_fee' => number_format($base_fee, 2),
                    'discount_amount' => number_format($discount_amount, 2),
                    'final_price' => number_format($final_price, 2)
                ];
                $stmt_fee->close();
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid or expired discount code.'];
        }
        $stmt_code->close();
    }
}

echo json_encode($response);
?>