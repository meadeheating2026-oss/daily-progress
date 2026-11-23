<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();
        return DB::table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    public function up(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if (!$this->hasIndex('projects', 'idx_projects_list_filter')) {
                    $table->index(
                        ['deleted_at', 'is_draft', 'is_archived', 'status', 'created_at'],
                        'idx_projects_list_filter'
                    );
                }
                if (!$this->hasIndex('projects', 'idx_projects_archived_sort')) {
                    $table->index(
                        ['deleted_at', 'is_archived', 'archived_at'],
                        'idx_projects_archived_sort'
                    );
                }
                if (!$this->hasIndex('projects', 'idx_projects_name')) {
                    $table->index('name', 'idx_projects_name');
                }
            });
        }

        if (Schema::hasTable('project_items')) {
            Schema::table('project_items', function (Blueprint $table) {
                if (!$this->hasIndex('project_items', 'idx_project_items_progress_cov')) {
                    $table->index(
                        ['project_id', 'is_measurable', 'total_quantity', 'completed_quantity'],
                        'idx_project_items_progress_cov'
                    );
                }
            });
        }

        if (Schema::hasTable('issues')) {
            Schema::table('issues', function (Blueprint $table) {
                if (!$this->hasIndex('issues', 'idx_issues_proj_status_created')) {
                    $table->index(
                        ['project_id', 'status', 'created_at'],
                        'idx_issues_proj_status_created'
                    );
                }
                if (!$this->hasIndex('issues', 'idx_issues_status_deadline')) {
                    $table->index(
                        ['status', 'deadline'],
                        'idx_issues_status_deadline'
                    );
                }
            });
        }

        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'user_id')) {
            Schema::table('employees', function (Blueprint $table) {
                if (!$this->hasIndex('employees', 'idx_employees_user_id')) {
                    $table->index('user_id', 'idx_employees_user_id');
                }
            });
        }

        if (Schema::hasTable('daily_progress')) {
            Schema::table('daily_progress', function (Blueprint $table) {
                if (!$this->hasIndex('daily_progress', 'idx_daily_progress_item_date')) {
                    $table->index(
                        ['project_item_id', 'progress_date', 'deleted_at'],
                        'idx_daily_progress_item_date'
                    );
                }
                if (!$this->hasIndex('daily_progress', 'idx_daily_progress_emp_item')) {
                    $table->index(
                        ['employee_id', 'project_item_id', 'deleted_at'],
                        'idx_daily_progress_emp_item'
                    );
                }
            });
        }

        if (Schema::hasTable('employee_project')) {
            Schema::table('employee_project', function (Blueprint $table) {
                if (!$this->hasIndex('employee_project', 'idx_emp_proj_emp_proj')) {
                    $table->index(['employee_id', 'project_id'], 'idx_emp_proj_emp_proj');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if ($this->hasIndex('projects', 'idx_projects_list_filter')) {
                    $table->dropIndex('idx_projects_list_filter');
                }
                if ($this->hasIndex('projects', 'idx_projects_archived_sort')) {
                    $table->dropIndex('idx_projects_archived_sort');
                }
                if ($this->hasIndex('projects', 'idx_projects_name')) {
                    $table->dropIndex('idx_projects_name');
                }
            });
        }

        if (Schema::hasTable('project_items')) {
            Schema::table('project_items', function (Blueprint $table) {
                if ($this->hasIndex('project_items', 'idx_project_items_progress_cov')) {
                    $table->dropIndex('idx_project_items_progress_cov');
                }
            });
        }

        if (Schema::hasTable('issues')) {
            Schema::table('issues', function (Blueprint $table) {
                if ($this->hasIndex('issues', 'idx_issues_proj_status_created')) {
                    $table->dropIndex('idx_issues_proj_status_created');
                }
                if ($this->hasIndex('issues', 'idx_issues_status_deadline')) {
                    $table->dropIndex('idx_issues_status_deadline');
                }
            });
        }

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if ($this->hasIndex('employees', 'idx_employees_user_id')) {
                    $table->dropIndex('idx_employees_user_id');
                }
            });
        }
    }
};
