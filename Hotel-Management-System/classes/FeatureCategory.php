<?php
require_once __DIR__ . '/Model.php';

//FeatureCategory Model - Handles feature category database operations

class FeatureCategory extends Model
{
    protected string $table = 'featureCategories';
    protected string $primaryKey = 'categoryID';

    public function getAllOrdered(): array
    {
        return $this->getAll('categoryName', 'ASC');
    }

    public function getAllNames(): array
    {
        $categories = $this->getAllOrdered();
        return array_column($categories, 'categoryName');
    }

    /**
     * Add a new category
     * 
     * @param string $categoryName Category name
     * @return array ['success' => bool, 'message' => string, 'id' => int|null]
     */
    public function addCategory(string $categoryName): array
    {
        $categoryName = trim($categoryName);
        
        if (empty($categoryName)) {
            return ['success' => false, 'message' => 'Category name cannot be empty', 'id' => null];
        }

        // Check if already exists
        if ($this->exists('categoryName', $categoryName)) {
            return ['success' => false, 'message' => "Category \"{$categoryName}\" already exists!", 'id' => null];
        }

        $id = $this->insert(['categoryName' => $categoryName]);
        
        if ($id) {
            return ['success' => true, 'message' => "Category \"{$categoryName}\" added successfully!", 'id' => $id];
        }
        
        return ['success' => false, 'message' => 'Error adding category. Please try again.', 'id' => null];
    }

    /**
     * Rename a category (also updates features using this category)
     * 
     * @param int $categoryID Category ID
     * @param string $newName New category name
     * @return array ['success' => bool, 'message' => string]
     */
    public function renameCategory(int $categoryID, string $newName): array
    {
        $newName = trim($newName);
        
        if (empty($newName)) {
            return ['success' => false, 'message' => 'Category name cannot be empty'];
        }

        // get old category name
        $category = $this->find($categoryID);
        if (!$category) {
            return ['success' => false, 'message' => 'Category not found'];
        }
        $oldName = $category['categoryName'];

        // check if new name already exists (excluding current)
        $query = "SELECT categoryID FROM `{$this->table}` WHERE `categoryName` = ? AND `categoryID` != ?";
        $result = $this->executeStatement($query, 'si', [$newName, $categoryID]);
        
        if ($result && $result->num_rows > 0) {
            return ['success' => false, 'message' => "Category \"{$newName}\" already exists!"];
        }

        // update category name
        if (!$this->update($categoryID, ['categoryName' => $newName])) {
            return ['success' => false, 'message' => 'Error renaming category. Please try again.'];
        }

        // update features using this category
        $updateFeaturesQuery = "UPDATE features SET category = ? WHERE category = ?";
        $this->executeStatement($updateFeaturesQuery, 'ss', [$newName, $oldName]);

        return ['success' => true, 'message' => "Category renamed from \"{$oldName}\" to \"{$newName}\" successfully!"];
    }

    //Delete a category (only if no features are using it)
    public function deleteCategory(int $categoryID): array
    {
        // get category name
        $category = $this->find($categoryID);
        if (!$category) {
            return ['success' => false, 'message' => 'Category not found'];
        }
        $categoryName = $category['categoryName'];

        // check if any features are using this category
        $query = "SELECT COUNT(*) as count FROM features WHERE category = ?";
        $result = $this->executeStatement($query, 's', [$categoryName]);
        
        if ($result) {
            $row = $this->db->fetchOne($result);
            $featureCount = (int)$row['count'];
            
            if ($featureCount > 0) {
                return ['success' => false, 'message' => "Cannot delete category \"{$categoryName}\". {$featureCount} feature(s) are using it."];
            }
        }

        if ($this->delete($categoryID)) {
            return ['success' => true, 'message' => "Category \"{$categoryName}\" deleted successfully!"];
        }
        
        return ['success' => false, 'message' => 'Error deleting category.'];
    }

    public function findByName(string $name): ?array
    {
        return $this->findOneBy('categoryName', $name);
    }

    public function getAllWithFeatureCount(): array
    {
        $query = "SELECT fc.*, COUNT(f.featureId) as featureCount 
                  FROM `{$this->table}` fc 
                  LEFT JOIN features f ON fc.categoryName = f.category 
                  GROUP BY fc.categoryID 
                  ORDER BY fc.categoryName";
        $result = $this->rawQuery($query);
        return $result ? $this->db->fetchAll($result) : [];
    }
}
