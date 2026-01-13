<?php
require_once __DIR__ . '/Model.php';

//Booking Model - Handles all booking-related database operations

class Booking extends Model
{
    protected string $table = 'bookings';
    protected string $primaryKey = 'bookingID';

    // Valid status values
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_REFUNDED = 'refunded';

    public function getAllWithDetails(string $orderBy = 'createdAt', string $direction = 'DESC'): array
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $query = "SELECT b.*, r.roomName, rt.roomType, u.firstName, u.lastName, u.email AS userEmail 
                  FROM `{$this->table}` b 
                  INNER JOIN rooms r ON b.roomID = r.roomID 
                  INNER JOIN roomtypes rt ON r.roomTypeId = rt.roomTypeID 
                  INNER JOIN users u ON b.userID = u.userID 
                  ORDER BY b.{$orderBy} {$direction}";
        $result = $this->rawQuery($query);
        return $result ? $this->db->fetchAll($result) : [];
    }

    public function getByStatusWithDetails($status): array
    {
        if (is_array($status)) {
            $statusList = "'" . implode("','", array_map([$this, 'escape'], $status)) . "'";
            $whereClause = "b.bookingStatus IN ({$statusList})";
        } else {
            $whereClause = "b.bookingStatus = '" . $this->escape($status) . "'";
        }

        $query = "SELECT b.*, r.roomName, rt.roomType, u.firstName, u.lastName, u.email AS userEmail 
                  FROM `{$this->table}` b 
                  INNER JOIN rooms r ON b.roomID = r.roomID 
                  INNER JOIN roomtypes rt ON r.roomTypeId = rt.roomTypeID 
                  INNER JOIN users u ON b.userID = u.userID 
                  WHERE {$whereClause} 
                  ORDER BY b.createdAt DESC";
        $result = $this->rawQuery($query);
        return $result ? $this->db->fetchAll($result) : [];
    }

    public function getPendingBookings(): array
    {
        return $this->getByStatusWithDetails(self::STATUS_PENDING);
    }

    public function getConfirmedBookings(): array
    {
        return $this->getByStatusWithDetails([self::STATUS_CONFIRMED, self::STATUS_COMPLETED]);
    }

    public function getCompletedBookings(): array
    {
        return $this->getByStatusWithDetails(self::STATUS_COMPLETED);
    }

    public function getByUserWithDetails(int $userID): array
    {
        $query = "SELECT b.*, r.roomName, r.imagePath, r.capacity, r.base_price, rt.roomType 
                  FROM `{$this->table}` b 
                  INNER JOIN rooms r ON b.roomID = r.roomID 
                  INNER JOIN roomtypes rt ON r.roomTypeId = rt.roomTypeID 
                  WHERE b.userID = ? 
                  ORDER BY b.createdAt DESC";
        $result = $this->executeStatement($query, 'i', [$userID]);
        return $result ? $this->db->fetchAll($result) : [];
    }

    public function getWithDetails(int $bookingID): ?array
    {
        $query = "SELECT b.*, r.roomName, rt.roomType, u.firstName, u.lastName, u.email 
                  FROM `{$this->table}` b 
                  INNER JOIN rooms r ON b.roomID = r.roomID 
                  INNER JOIN roomtypes rt ON r.roomTypeId = rt.roomTypeID 
                  INNER JOIN users u ON b.userID = u.userID 
                  WHERE b.bookingID = ?";
        $result = $this->executeStatement($query, 'i', [$bookingID]);
        
        if ($result && $result->num_rows > 0) {
            return $this->db->fetchOne($result);
        }
        
        return null;
    }

    public function getById(int $bookingID): ?array
    {
        return $this->find($bookingID);
    }

    public function getByIdWithDetails(int $bookingID): ?array
    {
        return $this->getWithDetails($bookingID);
    }

    public function createBooking(array $data)
    {
        $bookingData = [
            'userID' => $data['userID'],
            'roomID' => $data['roomID'],
            'fullName' => $data['fullName'],
            'email' => $data['email'],
            'phoneNumber' => $data['phoneNumber'],
            'checkInDate' => $data['checkInDate'],
            'checkOutDate' => $data['checkOutDate'],
            'numberOfGuests' => $data['numberOfGuests'],
            'totalPrice' => $data['totalPrice'],
            'paymentMethod' => $data['paymentMethod'],
            'paymentStatus' => $data['paymentStatus'] ?? self::PAYMENT_PENDING,
            'bookingStatus' => $data['bookingStatus'] ?? self::STATUS_PENDING
        ];

        $query = "INSERT INTO `{$this->table}` 
                  (userID, roomID, fullName, email, phoneNumber, checkInDate, checkOutDate, 
                   numberOfGuests, totalPrice, paymentMethod, paymentStatus, bookingStatus, createdAt, updatedAt) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            'iisssssissss',
            $bookingData['userID'],
            $bookingData['roomID'],
            $bookingData['fullName'],
            $bookingData['email'],
            $bookingData['phoneNumber'],
            $bookingData['checkInDate'],
            $bookingData['checkOutDate'],
            $bookingData['numberOfGuests'],
            $bookingData['totalPrice'],
            $bookingData['paymentMethod'],
            $bookingData['paymentStatus'],
            $bookingData['bookingStatus']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }

    /**
     * Update booking status
     * 
     * @param int $bookingID Booking ID
     * @param string $status New status
     * @return bool
     */
    public function updateStatus(int $bookingID, string $status): bool
    {
        $validStatuses = [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_CANCELLED, self::STATUS_COMPLETED];
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $query = "UPDATE `{$this->table}` SET bookingStatus = ?, updatedAt = NOW() WHERE bookingID = ?";
        return $this->executeStatement($query, 'si', [$status, $bookingID]) !== false;
    }

    public function updatePaymentStatus(int $bookingID, string $status): bool
    {
        $validStatuses = [self::PAYMENT_PENDING, self::PAYMENT_PAID, self::PAYMENT_REFUNDED];
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $query = "UPDATE `{$this->table}` SET paymentStatus = ?, updatedAt = NOW() WHERE bookingID = ?";
        return $this->executeStatement($query, 'si', [$status, $bookingID]) !== false;
    }

    public function confirm(int $bookingID): bool
    {
        return $this->updateStatus($bookingID, self::STATUS_CONFIRMED);
    }

    public function cancel(int $bookingID): bool
    {
        // Get booking details before cancelling
        $booking = $this->find($bookingID);
        if (!$booking) {
            return false;
        }
        
        // Check if booking was completed (if so, we need to restore room quantity)
        $wasCompleted = isset($booking['bookingStatus']) && $booking['bookingStatus'] === self::STATUS_COMPLETED;
        
        $query = "UPDATE `{$this->table}` 
                  SET bookingStatus = ?, paymentStatus = ?, updatedAt = NOW() 
                  WHERE bookingID = ?";
        $success = $this->executeStatement($query, 'ssi', [self::STATUS_CANCELLED, self::PAYMENT_REFUNDED, $bookingID]) !== false;
        
        // If booking was completed and cancellation was successful, increase room quantity back
        if ($success && $wasCompleted && isset($booking['roomID'])) {
            $roomModel = new Room();
            $roomModel->increaseQuantity($booking['roomID']);
        }
        
        return $success;
    }

    /**
     * Cancel booking by user (marks it as user-cancelled)
     * For confirmed bookings: sets to pending for admin approval
     * For pending bookings: sets to cancelled immediately
     * 
     * @param int $bookingID Booking ID
     * @param bool $isConfirmedBooking Whether this is a confirmed booking requiring refund approval
     * @param string $refundReason Optional refund reason from user
     * @return bool
     */
    public function cancelByUser(int $bookingID, bool $isConfirmedBooking = false, string $refundReason = ''): bool
    {
        if ($isConfirmedBooking) {
            // For confirmed bookings, set to pending (awaiting refund approval)
            // Store refund reason for admin review
            $query = "UPDATE `{$this->table}` 
                      SET bookingStatus = ?, cancelledByUser = 1, refundReason = ?, updatedAt = NOW() 
                      WHERE bookingID = ?";
            return $this->executeStatement($query, 'ssi', [self::STATUS_PENDING, $refundReason, $bookingID]) !== false;
        } else {
            // For pending bookings, we need to distinguish between paid and unpaid payments.
            // If payment was already paid, create a refund request and set to pending (awaiting admin approval)
            // (do NOT mark payment as refunded yet). For unpaid/pending payments, cancel immediately and mark refunded.
            $booking = $this->find($bookingID);
            if ($booking && isset($booking['paymentStatus']) && $booking['paymentStatus'] === self::PAYMENT_PAID) {
                // Mark as pending refund request so admins can approve and process the refund
                $query = "UPDATE `{$this->table}` 
                          SET bookingStatus = ?, cancelledByUser = 1, refundReason = ?, updatedAt = NOW() 
                          WHERE bookingID = ?";
                return $this->executeStatement($query, 'ssi', [self::STATUS_PENDING, $refundReason, $bookingID]) !== false;
            } else {
                // For unpaid bookings, cancel immediately and mark payment as refunded
                $query = "UPDATE `{$this->table}` 
                          SET bookingStatus = ?, paymentStatus = ?, cancelledByUser = 1, updatedAt = NOW() 
                          WHERE bookingID = ?";
                return $this->executeStatement($query, 'ssi', [self::STATUS_CANCELLED, self::PAYMENT_REFUNDED, $bookingID]) !== false;
            }
        }
    }

    /**
     * Check if booking was cancelled by user (completed refund)
     * Note: Pending refund requests are NOT blocked - admins can still process them
     * 
     * @param int $bookingID Booking ID
     * @return bool
     */
    public function isCancelledByUser(int $bookingID): bool
    {
        $booking = $this->find($bookingID);
        if (!$booking) {
            return false;
        }
        
        // Only block editing on COMPLETED refunds (cancelled status)
        // Pending refund requests can still be processed by admin
        return isset($booking['cancelledByUser']) && 
               $booking['cancelledByUser'] == 1 &&
               $booking['bookingStatus'] === self::STATUS_CANCELLED;
    }

    public function complete(int $bookingID): bool
    {
        // Get booking details before completing
        $booking = $this->find($bookingID);
        if (!$booking) {
            return false;
        }
        
        // Update booking status to completed
        $success = $this->updateStatus($bookingID, self::STATUS_COMPLETED);
        
        // If successful, decrease room quantity
        if ($success && isset($booking['roomID'])) {
            $roomModel = new Room();
            $roomModel->decreaseQuantity($booking['roomID']);
        }
        
        return $success;
    }

    public function getBookingForNotification(int $bookingID): ?array
    {
        $query = "SELECT b.phoneNumber, b.checkInDate, u.firstName, u.lastName 
                  FROM `{$this->table}` b 
                  INNER JOIN users u ON b.userID = u.userID 
                  WHERE b.bookingID = ?";
        $result = $this->executeStatement($query, 'i', [$bookingID]);
        
        if ($result && $result->num_rows > 0) {
            $data = $this->db->fetchOne($result);
            $data['customerName'] = trim($data['firstName'] . ' ' . $data['lastName']);
            return $data;
        }
        
        return null;
    }

    public function countByStatus(string $status): int
    {
        return $this->countBy('bookingStatus', $status);
    }

    public function getRecentForDropdown(int $limit = 50): array
    {
        $query = "SELECT b.bookingID, b.phoneNumber, u.firstName, u.lastName 
                  FROM `{$this->table}` b 
                  INNER JOIN users u ON b.userID = u.userID 
                  WHERE b.bookingStatus IN ('pending', 'confirmed') 
                  ORDER BY b.createdAt DESC 
                  LIMIT ?";
        $result = $this->executeStatement($query, 'i', [$limit]);
        return $result ? $this->db->fetchAll($result) : [];
    }

    public function updateBookingDetails(int $bookingID, string $bookingStatus, string $paymentStatus, string $notes = ''): bool
    {
        $validBookingStatuses = [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_CANCELLED, self::STATUS_COMPLETED];
        $validPaymentStatuses = [self::PAYMENT_PENDING, self::PAYMENT_PAID, self::PAYMENT_REFUNDED];

        if (!in_array($bookingStatus, $validBookingStatuses) || !in_array($paymentStatus, $validPaymentStatuses)) {
            return false;
        }

        $query = "UPDATE `{$this->table}` 
                  SET bookingStatus = ?, paymentStatus = ?, notes = ?, updatedAt = NOW() 
                  WHERE bookingID = ?";
        return $this->executeStatement($query, 'sssi', [$bookingStatus, $paymentStatus, $notes, $bookingID]) !== false;
    }
}
