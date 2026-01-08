<?php
require_once __DIR__ . '/Model.php';

//Feature Model - Handles room feature database operations

class Feature extends Model
{
    protected string $table = 'features';
    protected string $primaryKey = 'featureId';

    public function getAllOrdered(): array
    {
        $query = "SELECT * FROM `{$this->table}` ORDER BY category, featureId";
        $result = $this->rawQuery($query);
        return $result ? $this->db->fetchAll($result) : [];
    }

    public function getAllGroupedByCategory(): array
    {
        $features = $this->getAllOrdered();
        $grouped = [];
        
        foreach ($features as $feature) {
            $category = $feature['category'] ?? 'General';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $feature;
        }
        
        return $grouped;
    }

    public function findByName(string $featureName): ?array
    {
        return $this->findBy('featureName', $featureName);
    }
    public function addFeature(string $featureName, string $category): array
    {
        $featureName = trim($featureName);
        $category = trim($category);
        
        if (empty($featureName) || empty($category)) {
            return ['success' => false, 'message' => 'Feature name and category are required', 'id' => null];
        }

        $id = $this->insert([
            'featureName' => $featureName,
            'category' => $category
        ]);
        
        if ($id) {
            return ['success' => true, 'message' => 'Feature added successfully!', 'id' => $id];
        }
        
        return ['success' => false, 'message' => 'Error adding feature.', 'id' => null];
    }

    public function updateFeature(int $featureId, string $featureName, string $category): array
    {
        $featureName = trim($featureName);
        $category = trim($category);
        
        if (empty($featureName) || empty($category)) {
            return ['success' => false, 'message' => 'Feature name and category are required'];
        }

        if ($this->update($featureId, ['featureName' => $featureName, 'category' => $category])) {
            return ['success' => true, 'message' => 'Feature updated successfully!'];
        }
        
        return ['success' => false, 'message' => 'Error updating feature.'];
    }

    public function deleteFeature(int $featureId): array
    {
        // First delete from roomFeatures
        $this->rawQuery("DELETE FROM roomFeatures WHERE featureID = " . (int)$featureId);
        
        if ($this->delete($featureId)) {
            return ['success' => true, 'message' => 'Feature deleted successfully!'];
        }
        
        return ['success' => false, 'message' => 'Error deleting feature.'];
    }

    public function getByCategory(string $category): array
    {
        $query = "SELECT * FROM `{$this->table}` WHERE `category` = ? ORDER BY featureName";
        $result = $this->executeStatement($query, 's', [$category]);
        return $result ? $this->db->fetchAll($result) : [];
    }

    public function countByCategory(string $category): int
    {
        return $this->countBy('category', $category);
    }

    public function getAllWithRoomCount(): array
    {
        $query = "SELECT f.*, COUNT(rf.roomID) as roomCount 
                  FROM `{$this->table}` f 
                  LEFT JOIN roomFeatures rf ON f.featureId = rf.featureID 
                  GROUP BY f.featureId 
                  ORDER BY f.category, f.featureId";
        $result = $this->rawQuery($query);
        return $result ? $this->db->fetchAll($result) : [];
    }
}
