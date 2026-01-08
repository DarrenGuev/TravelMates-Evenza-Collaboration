<?php
require_once __DIR__ . '/Model.php';

//RoomType Model - Handles room type database operations

class RoomType extends Model
{
    protected string $table = 'roomTypes';
    protected string $primaryKey = 'roomTypeID';

    public function getAllOrdered(): array
    {
        return $this->getAll('roomTypeID', 'ASC');
    }

    public function addRoomType(string $roomTypeName): array
    {
        $roomTypeName = trim($roomTypeName);
        
        if (empty($roomTypeName)) {
            return ['success' => false, 'message' => 'Room type name cannot be empty', 'id' => null];
        }

        // Check if already exists
        if ($this->exists('roomType', $roomTypeName)) {
            return ['success' => false, 'message' => "Room type \"{$roomTypeName}\" already exists!", 'id' => null];
        }

        $id = $this->insert(['roomType' => $roomTypeName]);
        
        if ($id) {
            return ['success' => true, 'message' => "Room type \"{$roomTypeName}\" added successfully!", 'id' => $id];
        }
        
        return ['success' => false, 'message' => 'Error adding room type. Please try again.', 'id' => null];
    }

    public function updateRoomType(int $roomTypeID, string $newName): array
    {
        $newName = trim($newName);
        
        if (empty($newName)) {
            return ['success' => false, 'message' => 'Room type name cannot be empty'];
        }

        // Check if new name already exists (excluding current)
        $query = "SELECT roomTypeID FROM `{$this->table}` WHERE `roomType` = ? AND `roomTypeID` != ?";
        $result = $this->executeStatement($query, 'si', [$newName, $roomTypeID]);
        
        if ($result && $result->num_rows > 0) {
            return ['success' => false, 'message' => "Room type \"{$newName}\" already exists!"];
        }

        if ($this->update($roomTypeID, ['roomType' => $newName])) {
            return ['success' => true, 'message' => 'Room type updated successfully!'];
        }
        
        return ['success' => false, 'message' => 'Error updating room type. Please try again.'];
    }

    public function deleteRoomType(int $roomTypeID): array
    {
        // Check if any rooms are using this type
        $query = "SELECT COUNT(*) as count FROM rooms WHERE roomTypeId = ?";
        $result = $this->executeStatement($query, 'i', [$roomTypeID]);
        
        if ($result) {
            $row = $this->db->fetchOne($result);
            $roomCount = (int)$row['count'];
            
            if ($roomCount > 0) {
                return ['success' => false, 'message' => "Cannot delete this room type. {$roomCount} room(s) are using it."];
            }
        }

        if ($this->delete($roomTypeID)) {
            return ['success' => true, 'message' => 'Room type deleted successfully!'];
        }
        
        return ['success' => false, 'message' => 'Error deleting room type.'];
    }

    public function findByName(string $name): ?array
    {
        return $this->findOneBy('roomType', $name);
    }
}
