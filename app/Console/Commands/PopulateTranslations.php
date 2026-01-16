<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PopulateTranslations extends Command
{
    protected $signature = 'translations:populate {count=100000}';

    protected $description = 'Populate the database with a large number of translations for testing';

    public function handle()
    {
        ini_set('memory_limit', '512M');
        \Illuminate\Support\Facades\DB::disableQueryLog();
        \Illuminate\Support\Facades\DB::connection()->unsetEventDispatcher();
        
        $count = (int) $this->argument('count');
        $this->info("Starting population of {$count} translations...");

        $tags = ['mobile', 'desktop', 'web', 'ios', 'android', 'backend', 'frontend'];
        $tagIds = [];
        $tagMap = [];
        
        foreach ($tags as $tagName) {
            $existing = \Illuminate\Support\Facades\DB::table('tags')->where('name', $tagName)->first();
            if ($existing) {
                $tagIds[] = $existing->id;
                $tagMap[$tagName] = $existing->id;
            } else {
                $tagId = \Illuminate\Support\Facades\DB::table('tags')->insertGetId([
                    'name' => $tagName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $tagIds[] = $tagId;
                $tagMap[$tagName] = $tagId;
            }
        }
        $this->info("Tags ready.");

        $keyPatterns = [
            'app' => ['welcome', 'title', 'description', 'name', 'version', 'loading', 'error', 'success'],
            'button' => ['save', 'cancel', 'submit', 'delete', 'edit', 'create', 'update', 'close', 'back', 'next', 'previous', 'confirm'],
            'message' => ['success', 'error', 'warning', 'info', 'confirm', 'deleted', 'created', 'updated', 'saved'],
            'form' => ['label', 'placeholder', 'required', 'invalid', 'validation', 'submit', 'reset'],
            'error' => ['required', 'invalid', 'not_found', 'unauthorized', 'server', 'network', 'timeout', 'validation'],
            'page' => ['title', 'description', 'heading', 'subheading', 'footer', 'header'],
            'field' => ['email', 'password', 'name', 'phone', 'address', 'city', 'country', 'zip'],
            'status' => ['active', 'inactive', 'pending', 'completed', 'failed', 'processing'],
        ];

        $contentTemplates = [
            'en' => [
                'app.welcome' => ['Welcome', 'Welcome to {app}', 'Hello, welcome!'],
                'app.title' => ['{app} - Dashboard', '{app} Application', '{app} Platform'],
                'button.save' => ['Save', 'Save Changes', 'Save Now'],
                'button.cancel' => ['Cancel', 'Cancel Changes', 'Go Back'],
                'message.success' => ['Success!', 'Operation completed successfully', 'Done!'],
                'message.error' => ['An error occurred', 'Something went wrong', 'Error: {message}'],
                'form.required' => ['This field is required', 'Required field', 'Please fill this field'],
                'error.not_found' => ['Not found', 'Resource not found', '404 - Not Found'],
            ],
            'fr' => [
                'app.welcome' => ['Bienvenue', 'Bienvenue sur {app}', 'Bonjour, bienvenue !'],
                'app.title' => ['{app} - Tableau de bord', 'Application {app}', 'Plateforme {app}'],
                'button.save' => ['Enregistrer', 'Enregistrer les modifications', 'Enregistrer maintenant'],
                'button.cancel' => ['Annuler', 'Annuler les modifications', 'Retour'],
                'message.success' => ['Succès !', 'Opération réussie', 'Terminé !'],
                'message.error' => ['Une erreur est survenue', 'Quelque chose s\'est mal passé', 'Erreur : {message}'],
                'form.required' => ['Ce champ est requis', 'Champ requis', 'Veuillez remplir ce champ'],
                'error.not_found' => ['Non trouvé', 'Ressource non trouvée', '404 - Non trouvé'],
            ],
            'es' => [
                'app.welcome' => ['Bienvenido', 'Bienvenido a {app}', '¡Hola, bienvenido!'],
                'app.title' => ['{app} - Panel', 'Aplicación {app}', 'Plataforma {app}'],
                'button.save' => ['Guardar', 'Guardar cambios', 'Guardar ahora'],
                'button.cancel' => ['Cancelar', 'Cancelar cambios', 'Volver'],
                'message.success' => ['¡Éxito!', 'Operación completada con éxito', '¡Hecho!'],
                'message.error' => ['Ocurrió un error', 'Algo salió mal', 'Error: {message}'],
                'form.required' => ['Este campo es obligatorio', 'Campo obligatorio', 'Por favor complete este campo'],
                'error.not_found' => ['No encontrado', 'Recurso no encontrado', '404 - No encontrado'],
            ],
        ];

        $start = microtime(true);
        $batchSize = 1000;
        $batches = ceil($count / $batchSize);

        $bar = $this->output->createProgressBar($count);

        $lastId = \Illuminate\Support\Facades\DB::table('translations')->max('id') ?? 0;

        $mobileTags = array_filter($tagMap, function($tagId, $tagName) {
            return in_array($tagName, ['mobile', 'ios', 'android']);
        }, ARRAY_FILTER_USE_BOTH);
        $webTags = array_filter($tagMap, function($tagId, $tagName) {
            return in_array($tagName, ['web', 'desktop', 'frontend']);
        }, ARRAY_FILTER_USE_BOTH);
        $backendTags = array_filter($tagMap, function($tagId, $tagName) {
            return $tagName === 'backend';
        }, ARRAY_FILTER_USE_BOTH);

        for ($i = 0; $i < $batches; $i++) {
            $translations = [];
            $translationTags = [];
            
            for ($j = 0; $j < $batchSize; $j++) {
                if (($i * $batchSize + $j) >= $count) break;
                
                $currentId = $lastId + 1;
                $lastId++;

                $category = array_rand($keyPatterns);
                $subKey = $keyPatterns[$category][array_rand($keyPatterns[$category])];
                
                $randomSuffix = rand(1, 100) <= 20 ? '.' . rand(1, 1000) : '';
                $key = $category . '.' . $subKey . $randomSuffix;
                
                $localeRand = rand(1, 100);
                $locale = $localeRand <= 50 ? 'en' : ($localeRand <= 80 ? 'fr' : 'es');
                
                $content = $this->generateContent($key, $locale, $contentTemplates);
                
                $translations[] = [
                    'id' => $currentId,
                    'locale' => $locale,
                    'key' => $key,
                    'content' => $content,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (in_array($category, ['app', 'page', 'form'])) {
                    $tagPool = array_values(array_merge($webTags, $mobileTags, $backendTags));
                } elseif (in_array($category, ['button', 'field'])) {
                    $tagPool = rand(1, 100) <= 70 
                        ? array_values(array_merge($webTags, $mobileTags)) 
                        : array_values($backendTags);
                } else {
                    $tagPool = $tagIds;
                }
                
                if (empty($tagPool)) {
                    $tagPool = $tagIds;
                }
                
                $numTags = rand(1, min(3, count($tagPool)));
                $selectedTagIndices = (array) array_rand($tagPool, $numTags);
                
                foreach ($selectedTagIndices as $idx) {
                    $translationTags[] = [
                        'translation_id' => $currentId,
                        'tag_id' => $tagPool[$idx],
                    ];
                }
            }

            \Illuminate\Support\Facades\DB::table('translations')->insert($translations);
            \Illuminate\Support\Facades\DB::table('translation_tag')->insert($translationTags);

            unset($translations);
            unset($translationTags);
            
            $bar->advance($batchSize);
            
            if ($i % 10 === 0) {
                gc_collect_cycles();
            }
        }

        $bar->finish();
        $this->newLine();
        
        $duration = round(microtime(true) - $start, 2);
        $this->info("Completed in {$duration} seconds.");
    }

    private function generateContent(string $key, string $locale, array $templates): string
    {
        $keyParts = explode('.', $key);
        if (count($keyParts) >= 2) {
            $templateKey = $keyParts[0] . '.' . $keyParts[1];
            
            if (isset($templates[$locale][$templateKey])) {
                $template = $templates[$locale][$templateKey];
                $content = is_array($template) ? $template[array_rand($template)] : $template;
                
                $content = str_replace('{app}', ['MyApp', 'Application', 'Platform'][rand(0, 2)], $content);
                $content = str_replace('{message}', ['Invalid input', 'Server error', 'Network timeout'][rand(0, 2)], $content);
                
                return $content;
            }
        }
        
        $keyBase = implode('.', array_slice($keyParts, 0, 2));
        $translations = [
            'en' => [
                'app' => 'Application',
                'button' => 'Click here',
                'message' => 'Message',
                'form' => 'Form field',
                'error' => 'Error occurred',
                'page' => 'Page content',
                'field' => 'Field',
                'status' => 'Status',
            ],
            'fr' => [
                'app' => 'Application',
                'button' => 'Cliquez ici',
                'message' => 'Message',
                'form' => 'Champ de formulaire',
                'error' => 'Erreur survenue',
                'page' => 'Contenu de la page',
                'field' => 'Champ',
                'status' => 'Statut',
            ],
            'es' => [
                'app' => 'Aplicación',
                'button' => 'Haga clic aquí',
                'message' => 'Mensaje',
                'form' => 'Campo de formulario',
                'error' => 'Error ocurrido',
                'page' => 'Contenido de la página',
                'field' => 'Campo',
                'status' => 'Estado',
            ],
        ];
        
        $category = $keyParts[0] ?? 'app';
        $baseText = $translations[$locale][$category] ?? $translations['en'][$category] ?? 'Content';
        
        return $baseText . ' - ' . ucfirst(str_replace('_', ' ', $keyParts[1] ?? 'item'));
    }
}
