<?php
require_once __DIR__ . '/Model.php';

//Room Model - Handles all room-related database operations

class Room extends Model
{
    protected string $table = 'rooms';
    protected string $primaryKey = 'roomID';

    public function getAllWithType(string $orderBy = 'roomID', string $direction = 'ASC'): array
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $query = "SELECT rooms.*, roomTypes.roomType AS roomTypeName 
                  FROM `{$this->table}` 
                  INNER JOIN roomTypes ON rooms.roomTypeId = roomTypes.roomTypeID 
                  ORDER BY rooms.{$orderBy} {$direction}";
        $result = $this->rawQuery($query);
        
        return $result ? $this->db->fetchAll($result) : [];
    }

    public function getWithType(int $roomID): ?array
    {
        $query = "SELECT rooms.*, roomTypes.roomType AS roomTypeName 
                  FROM `{$this->table}` 
                  INNER JOIN roomTypes ON rooms.roomTypeId = roomTypes.roomTypeID 
                  WHERE rooms.roomID = ?";
        $result = $this->executeStatement($query, 'i', [$roomID]);
        
        if ($result && $result->num_rows > 0) {
            return $this->db->fetchOne($result);
        }
        
        return null;
    }

    public function addRoom(array $data)
    {
        return $this->insert([
            'roomName' => $data['roomName'],
            'roomTypeId' => $data['roomTypeId'],
            'capacity' => $data['capacity'],
            'quantity' => $data['quantity'],
            'base_price' => $data['base_price'],
            'imagePath' => $data['imagePath'] ?? ''
        ]);
    }

    public function updateRoom(int $roomID, array $data): bool
    {
        return $this->update($roomID, $data);
    }

    public function deleteRoom(int $roomID): bool
    {
        // Delete room features first
        $this->rawQuery("DELETE FROM roomFeatures WHERE roomID = " . (int)$roomID);
        
        // Delete the room
        return $this->delete($roomID);
    }

    public function getImagePath(int $roomID): ?string
    {
        $room = $this->find($roomID);
        return $room ? $room['imagePath'] : null;
    }

    public function getByType(int $roomTypeId): array
    {
        return $this->findBy('roomTypeId', $roomTypeId);
    }

    public function isAvailable(int $roomID, string $checkInDate, string $checkOutDate): bool
    {
        $room = $this->find($roomID);
        if (!$room || $room['quantity'] < 1) {
            return false;
        }

        $query = "SELECT COUNT(*) as count FROM bookings 
                  WHERE roomID = ? 
                  AND bookingStatus NOT IN ('cancelled', 'completed')
                  AND ((checkInDate <= ? AND checkOutDate > ?) 
                  OR (checkInDate < ? AND checkOutDate >= ?)
                  OR (checkInDate >= ? AND checkOutDate <= ?))";
        
        $result = $this->executeStatement($query, 'issssss', [
            $roomID, $checkOutDate, $checkInDate, $checkOutDate, $checkInDate, $checkInDate, $checkOutDate
        ]);
        
        if ($result) {
            $row = $this->db->fetchOne($result);
            return (int)$row['count'] < $room['quantity'];
        }
        
        return false;
    }

    public function getFeatures(int $roomID): array
    {
        $query = "SELECT f.featureId, f.featureName, f.category 
                  FROM features f 
                  INNER JOIN roomFeatures rf ON f.featureId = rf.featureID 
                  WHERE rf.roomID = ? 
                  ORDER BY f.category, f.featureName";
        $result = $this->executeStatement($query, 'i', [$roomID]);
        
        return $result ? $this->db->fetchAll($result) : [];
    }

    public function getFeaturesGrouped(int $roomID): array
    {
        $features = $this->getFeatures($roomID);
        $grouped = [];
        
        foreach ($features as $feature) {
            $category = $feature['category'] ?? 'General';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $feature['featureName'];
        }
        
        return $grouped;
    }

    public function setFeatures(int $roomID, array $featureIds): bool
    {
        // Delete existing features
        $this->rawQuery("DELETE FROM roomFeatures WHERE roomID = " . (int)$roomID);
        
        // Insert new features
        foreach ($featureIds as $featureId) {
            $featureId = (int)$featureId;
            $this->rawQuery("INSERT INTO roomFeatures (roomID, featureID) VALUES ({$roomID}, {$featureId})");
        }
        
        return true;
    }
    public function countByType(int $roomTypeId): int
    {
        return $this->countBy('roomTypeId', $roomTypeId);
    }
}
