<?php
require_once __DIR__ . '/Model.php';

//Feature Model - Handles room feature database operations

class Feature extends Model
{
    protected string $table = 'features';
    protected string $primaryKey = 'featureId';

    public function getAllOrdered(): array
    {
        $query = "SELECT f.*, fc.categoryName 
                  FROM `{$this->table}` f 
                  JOIN featurecategories fc ON f.categoryID = fc.categoryID 
                  ORDER BY fc.categoryName, f.featureId";
        $result = $this->rawQuery($query);
        return $result ? $this->db->fetchAll($result) : [];
    }

    public function getAllGroupedByCategory(): array
    {
        $features = $this->getAllOrdered();
        $grouped = [];
        
        foreach ($features as $feature) {
            $category = $feature['categoryName'] ?? 'General';
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
    
    public function addFeature(string $featureName, int $categoryID): array
    {
        $featureName = trim($featureName);
        
        if (empty($featureName) || $categoryID <= 0) {
            return ['success' => false, 'message' => 'Feature name and valid category are required', 'id' => null];
        }

        // Validate categoryID exists
        $categoryModel = new FeatureCategory();
        if (!$categoryModel->find($categoryID)) {
            return ['success' => false, 'message' => 'Invalid category selected', 'id' => null];
        }

        $id = $this->insert([
            'featureName' => $featureName,
            'categoryID' => $categoryID
        ]);
        
        if ($id) {
            return ['success' => true, 'message' => 'Feature added successfully!', 'id' => $id];
        }
        
        return ['success' => false, 'message' => 'Error adding feature.', 'id' => null];
    }

    public function updateFeature(int $featureId, string $featureName, int $categoryID): array
    {
        $featureName = trim($featureName);
        
        if (empty($featureName) || $categoryID <= 0) {
            return ['success' => false, 'message' => 'Feature name and valid category are required'];
        }

        // Validate categoryID exists
        $categoryModel = new FeatureCategory();
        if (!$categoryModel->find($categoryID)) {
            return ['success' => false, 'message' => 'Invalid category selected'];
        }

        if ($this->update($featureId, ['featureName' => $featureName, 'categoryID' => $categoryID])) {
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

    public function getByCategory(int $categoryID): array
    {
        $query = "SELECT f.*, fc.categoryName 
                  FROM `{$this->table}` f 
                  JOIN featurecategories fc ON f.categoryID = fc.categoryID 
                  WHERE f.categoryID = ? 
                  ORDER BY f.featureName";
        $result = $this->executeStatement($query, 'i', [$categoryID]);
        return $result ? $this->db->fetchAll($result) : [];
    }

    public function countByCategory(int $categoryID): int
    {
        return $this->countBy('categoryID', $categoryID);
    }

    public function getAllWithRoomCount(): array
    {
        $query = "SELECT f.*, fc.categoryName, COUNT(rf.roomID) as roomCount 
                  FROM `{$this->table}` f 
                  JOIN featurecategories fc ON f.categoryID = fc.categoryID 
                  LEFT JOIN roomFeatures rf ON f.featureId = rf.featureID 
                  GROUP BY f.featureId 
                  ORDER BY fc.categoryName, f.featureId";
        $result = $this->rawQuery($query);
        return $result ? $this->db->fetchAll($result) : [];
    }
}
