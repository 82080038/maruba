<?php
namespace App\Models;

class DocumentTemplate extends Model
{
    protected string $table = 'document_templates';
    protected array $fillable = [
        'name', 'type', 'template_content', 'variables', 'is_active', 'created_by'
    ];
    protected array $casts = [
        'is_active' => 'bool',
        'variables' => 'array',
        'created_by' => 'int',
        'created_at' => 'datetime'
    ];

    /**
     * Get templates by type
     */
    public function getByType(string $type): array
    {
        return $this->findWhere(['type' => $type, 'is_active' => true], ['name' => 'ASC']);
    }

    /**
     * Get active templates
     */
    public function getActiveTemplates(): array
    {
        return $this->findWhere(['is_active' => true], ['type' => 'ASC', 'name' => 'ASC']);
    }

    /**
     * Render template with variables
     */
    public function renderTemplate(int $templateId, array $variables): string
    {
        $template = $this->find($templateId);
        if (!$template) {
            throw new \Exception('Template not found');
        }

        $content = $template['template_content'];

        // Replace variables in template
        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', htmlspecialchars($value), $content);
        }

        return $content;
    }

    /**
     * Validate template variables
     */
    public function validateVariables(int $templateId, array $variables): array
    {
        $template = $this->find($templateId);
        if (!$template) {
            return ['valid' => false, 'error' => 'Template not found'];
        }

        $requiredVars = $template['variables'] ?? [];
        $missingVars = [];

        foreach ($requiredVars as $var) {
            if (!isset($variables[$var])) {
                $missingVars[] = $var;
            }
        }

        if (!empty($missingVars)) {
            return [
                'valid' => false,
                'error' => 'Missing required variables: ' . implode(', ', $missingVars)
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get template preview
     */
    public function getPreview(int $templateId, array $sampleData = []): string
    {
        $template = $this->find($templateId);
        if (!$template) {
            throw new \Exception('Template not found');
        }

        // Use sample data or default values
        $variables = array_merge([
            'document_number' => 'DOC-001/2024',
            'member_name' => 'John Doe',
            'loan_amount' => '5.000.000',
            'current_date' => date('d/m/Y'),
            'company_name' => 'APLIKASI KSP'
        ], $sampleData);

        return $this->renderTemplate($templateId, $variables);
    }
}
