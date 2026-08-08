<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Support\Str;

/**
 * Rule-based FAQ assistant for the public site. No external AI provider is
 * configured for this environment, so replies are matched from a small set
 * of intents plus the live service catalog. Swappable later for a real LLM
 * call by replacing the body of reply() — callers only depend on that method.
 */
class ChatbotResponder
{
    public function reply(string $message): string
    {
        $text = Str::lower(trim($message));

        if ($text === '') {
            return "I didn't quite catch that — could you type your question again?";
        }

        if (Str::contains($text, ['hello', 'hi ', 'hi!', 'hey', 'good morning', 'good afternoon', 'good evening']) || $text === 'hi') {
            return "Hey there! 👋 I'm the undek assistant, here 24/7. Ask me about our services, pricing, or how to get a quote — or I can point you to a real specialist.";
        }

        if (Str::contains($text, ['human', 'agent', 'real person', 'talk to someone', 'speak to', 'live chat', 'live agent'])) {
            return "Happy to connect you with a specialist. Submit a quick quote request and it opens a direct chat thread with whoever picks up your project — or email hello@undek.com any time.";
        }

        if (Str::contains($text, ['hour', '24/7', 'support hours', 'available', 'open now', 'when are you open'])) {
            return "We offer 24/7 support — someone is always reachable, day or night. For project work, a specialist typically replies within a couple of hours.";
        }

        if (Str::contains($text, ['price', 'pricing', 'cost', 'how much', 'budget', 'quote', 'quotation', 'estimate'])) {
            return "Pricing depends on scope, so we don't publish flat rates. The fastest way to get real numbers is to request a quote — tell us the service and describe the project, and a specialist will send a tailored quote, usually within a day.";
        }

        foreach (Service::where('is_active', true)->get() as $service) {
            $keywords = array_unique(array_filter(array_merge(
                [Str::lower($service->name)],
                explode(' ', Str::lower($service->name))
            ), fn ($word) => strlen($word) > 2));

            foreach ($keywords as $keyword) {
                if (Str::contains($text, $keyword)) {
                    $description = $service->description
                        ? Str::limit($service->description, 160)
                        : 'one of our core service lines.';

                    return "{$service->name} — {$description} Want a tailored quote for this? Submit a quick request and we'll take it from there.";
                }
            }
        }

        if (Str::contains($text, ['thank', 'thanks', 'bye', 'goodbye', 'appreciate'])) {
            return "You're welcome! Reach out any time — we're here 24/7. 🙌";
        }

        return "I might not have a perfect answer for that, but I can help with: our services (web development, SEO, IT support, graphic design, video editing), pricing/quotes, and connecting you with a specialist. What would you like to know?";
    }
}
