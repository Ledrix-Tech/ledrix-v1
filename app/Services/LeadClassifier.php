<?php

namespace App\Services;

use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Classifiers\NaiveBayes;

class LeadClassifier
{
    protected $estimator;
    protected $categoriesEstimator;
    protected $sentimentEstimator;

    protected $disposableDomains = [
        'mailinator.com',
        'tempmail.org',
        'tempmail.com',
        '10minutemail.com',
        'maildrop.cc',
        'yopmail.com',
        'trashmail.com',
        'guerrillamail.com',
    ];

    public function __construct()
    {
        // Training dataset for fake vs real
        $samples = [
            // FAKE
            ['buy cheap offer now', 'tempmail.org', 'invalid'],
            ['random spam free prize', 'mailinator.com', 'invalid'],
            ['win a free iphone click here', 'tempmail.com', 'invalid'],
            ['get instant loan at 0% interest', 'spamoffers.ru', 'invalid'],
            ['earn $5000 per week guaranteed', 'offers.ru', 'invalid'],
            ['click this link to claim reward', 'spammy.net', 'invalid'],
            ['cheap seo packages limited offer', 'cheap-seo.com', 'invalid'],
            ['congratulations you won lottery', 'spamlotto.com', 'invalid'],
            ['make money fast work from home', 'quickcash.biz', 'invalid'],
            ['just checking', 'abc.com', 'invalid'],
            ['free consultation click here', 'spam.com', 'invalid'],
            ['limited time discount contact us', 'promo.com', 'invalid'],

            // REAL
            ['i am interested in software development and wanted to know about it', 'gmail.com', 'valid'],
            ['hi i am looking for a web design agency to redesign our ecommerce store can you send me pricing', 'gmail.com', 'valid'],
            ['we need seo and digital marketing for our startup can we schedule a call', 'outlook.com', 'valid'],
            ['i would like to know your rates for mobile app development', 'yahoo.com', 'valid'],
            ['can you build a custom crm system for our business workflow', 'company.com', 'valid'],
            ['looking for a long term partner for website maintenance', 'hotmail.com', 'valid'],
            ['do you provide cloud hosting and server management services', 'gmail.com', 'valid'],
            ['please share your portfolio and client references for ecommerce sites', 'business.com', 'valid'],
            ['we need help integrating stripe and paypal into our site what are your fees', 'company.co', 'valid'],
            ['interested send details', 'example.org', 'valid'],
            ['question about pricing and timeline', 'devxperts.pro', 'valid'],
            ['need ecommerce dev serious buyer', 'startup.io', 'valid'],
        ];

        $labels = [
            'fake',
            'fake',
            'fake',
            'fake',
            'fake',
            'fake',
            'fake',
            'fake',
            'fake',
            'fake',
            'fake',
            'fake',
            'real',
            'real',
            'real',
            'real',
            'real',
            'real',
            'real',
            'real',
            'real',
            'real',
            'real',
            'real',
        ];

        $this->estimator = new NaiveBayes();
        $dataset = Labeled::build($samples, $labels);
        $this->estimator->train($dataset);

        /** Sentiment Training */
        $sentimentSamples = [
            // Negative
            ['not interested stop emailing me'],
            ['no budget right now'],
            ['very disappointed with service'],
            ['not happy with the pricing'],
            // Neutral
            ['interested in learning more'],
            ['can you send details please'],
            // Positive
            ['very excited to start working'],
            ['great service and support'],
            ['interested but frustrated with current provider'],
            ['need seo urgently because current provider failed'],
            ['looking for new partner after bad experience'],
        ];
        $sentimentLabels = [
            'negative',
            'negative',
            'negative',
            'negative',
            'neutral',
            'neutral',
            'positive',
            'positive',
            'positive',
            'positive',
            'positive'
        ];

        $this->sentimentEstimator = new NaiveBayes();
        $this->sentimentEstimator->train(Labeled::build($sentimentSamples, $sentimentLabels));

        $categorySamples = [
            // SEO
            ['we need seo and digital marketing services'],
            ['can you help with search engine optimization'],
            ['looking to rank our site higher in Google'],
            ['need help with backlinks and seo audit'],

            // Web Development
            ['looking for web design and development'],
            ['need a custom wordpress website'],
            ['can you build a responsive ecommerce site'],
            ['want a redesign of our company website'],

            // App Development
            ['can you build a mobile app'],
            ['need ios and android app developers'],
            ['looking to develop a cross platform mobile app'],
            ['want a native app for booking service'],

            // Hosting / Server
            ['need hosting and server management'],
            ['looking for cloud hosting and cpanel setup'],
            ['can you handle aws or digital ocean setup'],
            ['need a devops expert for server maintenance'],
        ];

        $categoryLabels = [
            'seo',
            'seo',
            'seo',
            'seo',
            'web',
            'web',
            'web',
            'web',
            'app',
            'app',
            'app',
            'app',
            'hosting',
            'hosting',
            'hosting',
            'hosting',
        ];

        $this->categoriesEstimator = new NaiveBayes();
        $this->categoriesEstimator->train(Labeled::build($categorySamples, $categoryLabels));
    }

    protected function normalizeText(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text);
        return preg_replace('/\s+/', ' ', trim($text));
    }

    protected function extractDomain(string $email): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '';
        }
        return mb_strtolower(substr(strrchr($email, "@"), 1) ?: '');
    }

    protected function phoneStatus(?string $phone): string
    {
        if (empty($phone)) {
            return 'invalid';
        }
        $clean = preg_replace('/[^0-9\+]/', '', $phone);
        return preg_match('/^\+?[0-9]{7,15}$/', $clean) ? 'valid' : 'invalid';
    }

    protected function adjustSentiment(string $message, string $sentiment): string
    {
        $msg = $this->normalizeText($message);
        if (preg_match('/\b(buy|need|pricing|quote|rates|fees|hire|interested|discuss|seo|project)\b/', $msg)) {
            if ($sentiment === 'negative') {
                return 'positive'; // override
            }
        }
        return $sentiment;
    }

    protected function normalizeService(?string $service): string
    {
        if (!$service) return 'unknown';
        $map = [
            'logo design'                   => 'design',
            'logo animation'                => 'animation',
            'video animation'               => 'animation',
            'content development'           => 'content',
            'website design & development'  => 'web',
            'search engine optimization'    => 'seo',
            'social media marketing'        => 'smm',
            'merchandise'                   => 'branding',
            'packaging & labels'            => 'branding',
            'marketing collateral'          => 'branding',
            'domain & hosting'              => 'hosting',
            'online reputation management'  => 'seo',
        ];
        $key = strtolower(trim($service));
        return $map[$key] ?? 'unknown';
    }

    public function classify(array $leadData): array
    {
        $email   = $leadData['email'] ?? '';
        $phone   = $leadData['phone'] ?? '';
        $message = $leadData['message'] ?? '';
        $service = $leadData['service'] ?? null;

        // Quick filtering
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'fake', 'score' => null, 'strength' => null, 'category' => null, 'sentiment' => null];
        }
        $domain = $this->extractDomain($email);
        if (in_array($domain, $this->disposableDomains, true)) {
            return ['status' => 'fake', 'score' => null, 'strength' => null, 'category' => null, 'sentiment' => null];
        }
        $phoneStatus = $this->phoneStatus($phone);

        // Fake vs Real Classification
        $features = [
            $this->normalizeText($message),
            $domain ?: 'unknown',
            $phoneStatus,
        ];
        $dataset    = Unlabeled::build([$features]);
        $prediction = $this->estimator->predict($dataset);
        $status     = $prediction[0];

        if ($status === 'fake' && $phoneStatus === 'invalid' && $domain === 'unknown') {
            return ['status' => 'fake', 'score' => null, 'strength' => null, 'category' => null, 'sentiment' => null];
        }

        // Category: use service if provided, else ML fallback
        $category = $service
            ? $this->normalizeService($service)
            : $this->categoriesEstimator->predict(
                Unlabeled::build([[$this->normalizeText($message)]])
            )[0] ?? 'unknown';

        // Sentiment
        $sentiment = $this->sentimentEstimator
            ->predict(Unlabeled::build([[$this->normalizeText($message)]]))[0] ?? 'neutral';
        $sentiment = $this->adjustSentiment($message, $sentiment);

        // Score
        $result = $this->scoreLead($message, $domain, $phoneStatus, $sentiment);

        return [
            'status'    => 'real',
            'score'     => $result['score'],
            'strength'  => $result['strength'],
            'category'  => $category,
            'sentiment' => $sentiment,
        ];
    }

    protected function scoreLead(string $message, string $domain, string $phone, string $sentiment): array
    {
        $score = 50; // base score
        $msg   = $this->normalizeText($message);
        if ($phone === 'valid') $score += 20;
        if (!in_array($domain, $this->disposableDomains)) $score += 10;
        if ($sentiment === 'positive') $score += 15;
        if ($sentiment === 'negative') $score -= 15;
        if (preg_match('/\b(buy|pricing|quote|rates|fees|hire)\b/', $msg)) $score += 20;
        $score = max(0, min(100, $score));
        $strength = $this->classifyLeadStrength($score);
        return [
            'score'    => $score,
            'strength' => $strength
        ];
    }

    protected function classifyLeadStrength(int $score): string
    {
        if ($score >= 75) return 'hot';
        if ($score >= 40) return 'warm';
        return 'cold';
    }
}
