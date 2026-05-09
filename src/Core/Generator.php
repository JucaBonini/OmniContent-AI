<?php
namespace OmniContentAI\Core;

class Generator {
    private $api_key_gemini;
    private $api_key_openai;
    private $api_key_claude;

    public function __construct() {
        $this->api_key_gemini = get_option('ocai_api_gemini');
        $this->api_key_openai = get_option('ocai_api_openai');
        $this->api_key_claude = get_option('ocai_api_claude');
    }

    public function generate_content($keyword, $niche = 'general', $intent = 'informational') {
        $prompt = $this->get_system_prompt($keyword, $niche, $intent);

        if (!empty($this->api_key_openai)) {
            return $this->call_openai($prompt);
        }

        if (!empty($this->api_key_gemini)) {
            return $this->call_gemini($prompt);
        }

        return new \WP_Error('no_api_key', 'Nenhuma chave de API configurada.');
    }

    private function get_system_prompt($keyword, $niche, $intent) {
        $lang_code = get_option('gan_target_language', 'pt_BR');
        $languages = ['pt_BR' => 'Português (Brasil)', 'en_US' => 'English (USA)', 'es_ES' => 'Español (España)'];
        $target_lang = $languages[$lang_code] ?? 'Português (Brasil)';

        // 1. Definição da Intenção de Busca
        $intent_guides = [
            'informational' => "FOCO: Educação e Resposta Direta. O usuário quer saber 'O quê', 'Por que' ou 'Como'. Use tom didático.",
            'commercial'    => "FOCO: Comparação e Investigação. O usuário está decidindo uma compra. Use tom analítico, cite Prós/Contras e Tabelas.",
            'navigational'  => "FOCO: Autoridade de Marca. O usuário quer encontrar informações oficiais ou guias específicos da marca.",
            'transactional' => "FOCO: Ação e Conversão. O usuário está pronto para agir (comprar, baixar, simular). Use CTAs fortes e senso de urgência."
        ];
        $current_intent = $intent_guides[$intent] ?? $intent_guides['informational'];

        $base_prompt = "Você é um Especialista Sênior e Autoridade Máxima em SEO Generativo (GEO), AEO e E-E-A-T. Idioma: [$target_lang]. Alvo: [$keyword].\n";
        $base_prompt .= "INTENÇÃO DE BUSCA: [$current_intent]\n";
        $base_prompt .= "DIRETRIZES CRÍTICAS:\n";
        $base_prompt .= "- Escreva como um humano especialista com 20 anos de experiência.\n";
        $base_prompt .= "- Primeiro parágrafo deve ser um 'Answer Snippet' de 40-50 palavras.\n";
        $base_prompt .= "- Use Markdown rico (tabelas, listas, negrito).\n";
        $base_prompt .= "- O conteúdo deve ser original e passar em detectores de IA.\n";

        // 2. Construção dos Nichos com Dados para Schema
        if ($niche === 'expert_authority') {
            return $base_prompt . "Nicho: Engenharia, Sustentabilidade e Capital Consciente. Estrutura JSON:\n {
                \"titles\": [\"3 opções\"], 
                \"schema_type\": \"Article\",
                \"content_parts\": {
                    \"intro\":\"Snippet focado em AEO\",
                    \"full_article\":\"Conteúdo denso com H2/H3 e dados técnicos.\",
                    \"faq\":[{\"q\":\"\",\"a\":\"\"}],
                    \"meta_description\":\"\"
                }
            }";
        }

        if ($niche === 'product_comparison') {
            return $base_prompt . "Nicho: Comparativo de Produtos (Afiliados). Estrutura JSON:\n {
                \"titles\": [\"3 opções\"], 
                \"schema_data\": {\"type\":\"Review\",\"rating\":\"4.5\",\"pros\":[],\"cons\":[],\"winner\":\"\"},
                \"content_parts\": {
                    \"comparison_table\":\"Tabela Markdown\",
                    \"full_article\":\"Análise técnica comparativa.\",
                    \"faq\":[{\"q\":\"\",\"a\":\"\"}],
                    \"meta_description\":\"\"
                }
            }";
        }

        if ($niche === 'educational_tutorial') {
            return $base_prompt . "Nicho: Tutorial/How-To. Estrutura JSON:\n {
                \"titles\": [\"3 opções\"], 
                \"schema_data\": {\"type\":\"HowTo\",\"steps\":[{\"title\":\"\",\"text\":\"\"}]},
                \"content_parts\": {
                    \"intro\":\"O que será aprendido\",
                    \"full_article\":\"Guia passo a passo detalhado.\",
                    \"faq\":[{\"q\":\"\",\"a\":\"\"}],
                    \"meta_description\":\"\"
                }
            }";
        }

        if ($niche === 'local_expert') {
            return $base_prompt . "Nicho: SEO Local ($keyword). Estrutura JSON:\n {
                \"titles\": [\"3 opções\"], 
                \"schema_type\": \"LocalBusiness\",
                \"content_parts\": {
                    \"intro\":\"Contexto regional\",
                    \"full_article\":\"Guia focado em leis, clima e empresas locais.\",
                    \"faq\":[{\"q\":\"\",\"a\":\"\"}],
                    \"meta_description\":\"\"
                }
            }";
        }

        // Fallback Geral
        return $base_prompt . "Nicho: Geral. Estrutura JSON:\n {
            \"titles\": [\"3 opções\"], \"schema_type\": \"Article\",
            \"content_parts\": {\"intro\":\"\",\"full_article\":\"\",\"faq\":[{\"q\":\"\",\"a\":\"\"}],\"meta_description\":\"\"}
        }";
    }
}
