<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Array of SQL statements in proper execution order
        $statements = [
            // Create base table
            "CREATE TABLE IF NOT EXISTS public.tr1884_matvstats_t_stats
            (
                mv_name text COLLATE pg_catalog.\"default\",
                create_mv timestamp without time zone,
                mod_mv timestamp without time zone,
                refresh_mv_last timestamp without time zone,
                refresh_count integer DEFAULT 0,
                refresh_mv_time_last interval,
                refresh_mv_time_total interval DEFAULT '00:00:00'::interval,
                refresh_mv_time_min interval,
                refresh_mv_time_max interval,
                reset_last timestamp without time zone
            )",

            // Create view
            "CREATE OR REPLACE VIEW public.tr1884_matvstats_v_stats AS
             SELECT 
                mv_name,
                create_mv,
                mod_mv,
                refresh_mv_last,
                refresh_count,
                refresh_mv_time_last,
                refresh_mv_time_total,
                refresh_mv_time_min,
                refresh_mv_time_max,
                reset_last
             FROM public.tr1884_matvstats_t_stats",

            // Create main trigger function
            "CREATE OR REPLACE FUNCTION public.tr1884_matvstats_fn_trigger()
                RETURNS event_trigger
                LANGUAGE plpgsql
                COST 100
                VOLATILE NOT LEAKPROOF SECURITY DEFINER
            AS \$BODY\$
            DECLARE 
                r RECORD; 
                flag boolean; 
                t_refresh_total interval;
            BEGIN
                FOR r IN SELECT * FROM pg_event_trigger_ddl_commands()
                LOOP
                    IF tg_tag = 'CREATE MATERIALIZED VIEW' THEN
                        INSERT INTO public.tr1884_matvstats_t_stats (mv_name, create_mv) 
                        VALUES (r.object_identity, now());
                    END IF;
                    
                    IF tg_tag = 'ALTER MATERIALIZED VIEW' THEN
                        SELECT TRUE INTO flag 
                        FROM public.tr1884_matvstats_t_stats 
                        WHERE mv_name = r.object_identity;
                        
                        IF NOT FOUND THEN
                            INSERT INTO public.tr1884_matvstats_t_stats (mv_name, create_mv) 
                            VALUES (r.object_identity, now());
                            DELETE FROM public.tr1884_matvstats_t_stats 
                            WHERE mv_name NOT IN (SELECT schemaname || '.' || matviewname FROM pg_catalog.pg_matviews);
                        ELSE
                            UPDATE public.tr1884_matvstats_t_stats 
                            SET mod_mv = now() 
                            WHERE mv_name = r.object_identity;
                        END IF;
                    END IF;
                    
                    IF tg_tag = 'REFRESH MATERIALIZED VIEW' THEN
                        t_refresh_total := clock_timestamp() - (SELECT current_setting('mv_stats.start')::timestamp);
                        SET mv_stats.start to default;
                        
                        UPDATE public.tr1884_matvstats_t_stats 
                        SET 
                            refresh_mv_last = now(),
                            refresh_count = refresh_count + 1,
                            refresh_mv_time_last = t_refresh_total,
                            refresh_mv_time_total = refresh_mv_time_total + t_refresh_total,
                            refresh_mv_time_min = (
                                CASE 
                                    WHEN refresh_mv_time_min IS NULL THEN t_refresh_total
                                    WHEN refresh_mv_time_min IS NOT NULL AND refresh_mv_time_min > t_refresh_total THEN t_refresh_total
                                    ELSE refresh_mv_time_min
                                END
                            ),
                            refresh_mv_time_max = (
                                CASE 
                                    WHEN refresh_mv_time_max IS NULL THEN t_refresh_total
                                    WHEN refresh_mv_time_max IS NOT NULL AND refresh_mv_time_max < t_refresh_total THEN t_refresh_total
                                    ELSE refresh_mv_time_max
                                END
                            )
                        WHERE mv_name = r.object_identity;
                    END IF;
                END LOOP;
            END;
            \$BODY\$",

            // Create drop trigger function
            "CREATE OR REPLACE FUNCTION public.tr1884_matvstats_fn_trigger_drop()
                RETURNS event_trigger
                LANGUAGE plpgsql
                COST 100
                VOLATILE NOT LEAKPROOF SECURITY DEFINER
            AS \$BODY\$
            DECLARE 
                r RECORD;
            BEGIN
                FOR r IN SELECT * FROM pg_event_trigger_dropped_objects()
                LOOP
                    DELETE FROM public.tr1884_matvstats_t_stats 
                    WHERE mv_name = r.object_identity;
                END LOOP;
            END;
            \$BODY\$",

            // Create start trigger function
            "CREATE OR REPLACE FUNCTION public.tr1884_matvstats_fn_trigger_start()
                RETURNS event_trigger
                LANGUAGE plpgsql
                COST 100
                VOLATILE NOT LEAKPROOF SECURITY DEFINER
            AS \$BODY\$
            BEGIN
                PERFORM set_config('mv_stats.start', clock_timestamp()::text, true);
            END;
            \$BODY\$",

            // Create init function
            "CREATE OR REPLACE FUNCTION public.tr1884_matvstats_fn_init()
                RETURNS SETOF text
                LANGUAGE sql
                COST 100
                VOLATILE PARALLEL UNSAFE
                ROWS 1000
            AS \$BODY\$
                INSERT INTO public.tr1884_matvstats_t_stats (mv_name)
                SELECT schemaname || '.' || matviewname 
                FROM pg_catalog.pg_matviews 
                WHERE schemaname || '.' || matviewname NOT IN (
                    SELECT mv_name FROM public.tr1884_matvstats_t_stats
                )
                RETURNING mv_name;
            \$BODY\$",

            // Create reset stats function
            "CREATE OR REPLACE FUNCTION public.tr1884_matvstats_fn_reset_stats(
                VARIADIC mview text[] DEFAULT ARRAY['*'::text]
            )
                RETURNS SETOF text
                LANGUAGE plpgsql
                COST 100
                VOLATILE PARALLEL UNSAFE
                ROWS 1000
            AS \$BODY\$
            DECLARE 
                v text;
            BEGIN
                FOREACH v IN ARRAY \$1 LOOP
                    IF v = '*' THEN
                        RETURN QUERY 
                        UPDATE public.tr1884_matvstats_t_stats 
                        SET 
                            refresh_mv_last = NULL,
                            refresh_count = 0,
                            refresh_mv_time_last = NULL,
                            refresh_mv_time_total = '00:00:00',
                            refresh_mv_time_min = NULL,
                            refresh_mv_time_max = NULL,
                            reset_last = now() 
                        RETURNING mv_name;
                    ELSE
                        RETURN QUERY 
                        UPDATE public.tr1884_matvstats_t_stats 
                        SET 
                            refresh_mv_last = NULL,
                            refresh_count = 0,
                            refresh_mv_time_last = NULL,
                            refresh_mv_time_total = '00:00:00',
                            refresh_mv_time_min = NULL,
                            refresh_mv_time_max = NULL,
                            reset_last = now() 
                        WHERE mv_name = v 
                        RETURNING mv_name;
                    END IF;
                END LOOP;
                RETURN;
            END;
            \$BODY\$",

            // Create drop objects function
            "CREATE OR REPLACE FUNCTION public.tr1884_matvstats_fn_drop_objects()
                RETURNS void
                LANGUAGE sql
                COST 100
                VOLATILE PARALLEL UNSAFE
            AS \$BODY\$
                DROP EVENT TRIGGER IF EXISTS tr1884_matvstats_tr_start;
                DROP EVENT TRIGGER IF EXISTS tr1884_matvstats_tr_drop;
                DROP EVENT TRIGGER IF EXISTS tr1884_matvstats_tr_main;
                DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_trigger_start();
                DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_trigger_drop();
                DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_trigger();
                DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_reset_stats(VARIADIC text[]);
                DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_init();
                DROP VIEW IF EXISTS public.tr1884_matvstats_v_stats;
                DROP TABLE IF EXISTS public.tr1884_matvstats_t_stats;
                DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_drop_objects();
            \$BODY\$",

            // Create Event Triggers
            "CREATE EVENT TRIGGER tr1884_matvstats_tr_main ON ddl_command_end
                WHEN TAG IN ('CREATE MATERIALIZED VIEW', 'ALTER MATERIALIZED VIEW', 'REFRESH MATERIALIZED VIEW')
                EXECUTE FUNCTION public.tr1884_matvstats_fn_trigger()",

            "CREATE EVENT TRIGGER tr1884_matvstats_tr_drop ON sql_drop
                EXECUTE FUNCTION public.tr1884_matvstats_fn_trigger_drop()",

            "CREATE EVENT TRIGGER tr1884_matvstats_tr_start ON ddl_command_start
                WHEN TAG IN ('REFRESH MATERIALIZED VIEW')
                EXECUTE FUNCTION public.tr1884_matvstats_fn_trigger_start()"
        ];

        // Execute each statement individually
        foreach ($statements as $index => $statement) {
            try {
                DB::unprepared($statement);
                Log::info("Successfully executed statement $index");
            } catch (\Exception $e) {
                Log::error("Failed to execute statement $index: " . $e->getMessage());
                throw $e;
            }
        }

        // Verify functions exist
        try {
            $result = DB::select("SELECT proname FROM pg_proc WHERE proname LIKE 'tr1884_matvstats%'");
            Log::info("Created functions: " . json_encode($result));
        } catch (\Exception $e) {
            Log::error("Failed to verify functions: " . $e->getMessage());
        }
    }

    public function down(): void
    {
        // Drop objects in reverse order for clean removal
        $dropStatements = [
            "DROP EVENT TRIGGER IF EXISTS tr1884_matvstats_tr_start",
            "DROP EVENT TRIGGER IF EXISTS tr1884_matvstats_tr_drop",
            "DROP EVENT TRIGGER IF EXISTS tr1884_matvstats_tr_main",
            "DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_trigger_start()",
            "DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_trigger_drop()",
            "DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_trigger()",
            "DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_reset_stats(VARIADIC text[])",
            "DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_init()",
            "DROP VIEW IF EXISTS public.tr1884_matvstats_v_stats",
            "DROP TABLE IF EXISTS public.tr1884_matvstats_t_stats",
            "DROP FUNCTION IF EXISTS public.tr1884_matvstats_fn_drop_objects()"
        ];

        foreach ($dropStatements as $statement) {
            DB::unprepared($statement);
        }
    }
};
