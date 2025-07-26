<?php

namespace OpenBackend\LaravelPermission\Commands;

use Illuminate\Console\Command;
use OpenBackend\LaravelPermission\Services\PermissionTemplateService;

class SuggestPermissionsCommand extends Command
{
    protected $signature = 'permission:suggest 
                            {context? : Context for suggestions (e.g., blog, api, user)}
                            {--limit=20 : Limit number of suggestions}';

    protected $description = 'Get AI-powered permission suggestions based on context';

    public function handle()
    {
        $context = $this->argument('context') ?: '';
        $limit = (int) $this->option('limit');
        
        $service = new PermissionTemplateService();

        try {
            $suggestions = $service->getPermissionSuggestions($context);
            
            if ($limit > 0) {
                $suggestions = array_slice($suggestions, 0, $limit);
            }

            if (empty($suggestions)) {
                $this->info('No suggestions found for the given context.');
                return 0;
            }

            $this->info("Permission Suggestions" . ($context ? " for '{$context}'" : "") . ":");
            
            $groupedSuggestions = collect($suggestions)->groupBy('category');
            
            foreach ($groupedSuggestions as $category => $categoryItems) {
                $this->info("\n📁 {$category}:");
                
                foreach ($categoryItems as $suggestion) {
                    $this->line("  • {$suggestion['name']}");
                    $this->line("    {$suggestion['description']}");
                }
            }

            $this->info("\n💡 Use these suggestions to create permissions:");
            $this->line("    php artisan permission:create 'permission name' --description='description'");

        } catch (\Exception $e) {
            $this->error("Failed to generate suggestions: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
