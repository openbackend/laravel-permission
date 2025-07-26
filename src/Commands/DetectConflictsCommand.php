<?php

namespace OpenBackend\LaravelPermission\Commands;

use Illuminate\Console\Command;
use OpenBackend\LaravelPermission\Services\ConflictDetectionService;

class DetectConflictsCommand extends Command
{
    protected $signature = 'permission:detect-conflicts 
                            {--fix : Automatically fix conflicts where possible}';

    protected $description = 'Detect and optionally fix permission conflicts';

    public function handle()
    {
        $service = new ConflictDetectionService();
        $autoFix = $this->option('fix');

        $this->info('Scanning for permission conflicts...');

        try {
            $conflicts = $service->detectConflicts();
            $totalConflicts = array_sum(array_map('count', $conflicts));

            if ($totalConflicts === 0) {
                $this->info('✅ No conflicts detected! Your permission system is clean.');
                return 0;
            }

            $this->warn("Found {$totalConflicts} conflicts:");

            foreach ($conflicts as $category => $categoryConflicts) {
                if (empty($categoryConflicts)) {
                    continue;
                }

                $this->info("\n" . ucwords(str_replace('_', ' ', $category)) . ":");
                
                foreach ($categoryConflicts as $conflict) {
                    $severity = $conflict['severity'] ?? 'medium';
                    $icon = $severity === 'high' ? '🔴' : ($severity === 'medium' ? '🟡' : '🟢');
                    
                    $this->line("  {$icon} {$conflict['type']}");
                    
                    if (isset($conflict['role_name'])) {
                        $this->line("     Role: {$conflict['role_name']}");
                    }
                    
                    if (isset($conflict['pattern'])) {
                        $this->line("     Pattern: " . implode(' vs ', $conflict['pattern']));
                    }
                    
                    if (isset($conflict['depth'])) {
                        $this->line("     Depth: {$conflict['depth']} (max: {$conflict['max_allowed']})");
                    }
                }
            }

            if ($autoFix) {
                $this->info("\n🔧 Attempting to auto-fix conflicts...");
                $fixed = $service->autoFixConflicts($conflicts);
                
                if (!empty($fixed)) {
                    $this->info("\nAuto-fix Results:");
                    foreach ($fixed as $fix) {
                        $this->line("  ✓ {$fix}");
                    }
                } else {
                    $this->warn("No conflicts could be auto-fixed. Manual intervention required.");
                }
            } else {
                $this->info("\n💡 Run with --fix to automatically resolve conflicts where possible.");
            }

        } catch (\Exception $e) {
            $this->error("Conflict detection failed: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
