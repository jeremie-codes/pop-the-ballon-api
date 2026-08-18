<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Support — Pop The Ballon</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('images/ballon.png') }}">

    <meta name="description" content="Besoin d'aide avec Pop The Ballon ? Consultez notre support ou contactez-nous.">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f45164',
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen text-gray-900 bg-gray-50">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="flex items-center justify-between max-w-5xl px-6 py-5 mx-auto">

            <a href="/" class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-primary">
                    <span class="text-lg font-bold text-white">P</span>
                </div>

                <span class="text-xl font-bold">
                    Pop The Ballon
                </span>
            </a>

            <a href="/" class="text-sm font-medium text-gray-600 transition hover:text-primary">
                Accueil
            </a>

        </div>
    </header>


    <!-- Main -->
    <main class="max-w-5xl px-6 py-16 mx-auto">

        <!-- Hero -->
        <section class="text-center">

            <span class="inline-flex px-4 py-2 text-sm font-semibold rounded-full bg-primary/10 text-primary">
                Centre d'assistance
            </span>

            <h1 class="mt-6 text-4xl font-bold tracking-tight sm:text-5xl">
                Comment pouvons-nous vous aider ?
            </h1>

            <p class="max-w-2xl mx-auto mt-5 text-lg leading-8 text-gray-600">
                Vous rencontrez un problème avec Pop The Ballon ?
                Consultez les questions fréquentes ou contactez notre équipe
                d'assistance.
            </p>

        </section>


        <!-- Help cards -->
        <section class="grid gap-6 mt-14 md:grid-cols-3">

            <!-- Account -->
            <div class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">

                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary/10">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>

                <h2 class="mt-5 text-lg font-bold">
                    Compte
                </h2>

                <p class="mt-2 text-sm leading-6 text-gray-600">
                    Problème de connexion, d'inscription, de profil ou de
                    vérification de votre compte ?
                </p>

            </div>


            <!-- Messages -->
            <div class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">

                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary/10">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4-.82L3 21l1.82-4.09A7.61 7.61 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>

                <h2 class="mt-5 text-lg font-bold">
                    Messages
                </h2>

                <p class="mt-2 text-sm leading-6 text-gray-600">
                    Vous avez un problème avec les messages, les conversations
                    ou les notifications ?
                </p>

            </div>


            <!-- Paiements -->
            <div class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">

                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary/10">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h2m2 0h2m-8 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>

                <h2 class="mt-5 text-lg font-bold">
                    Paiements
                </h2>

                <p class="mt-2 text-sm leading-6 text-gray-600">
                    Une question concernant vos achats, forfaits de messages
                    ou paiements ?
                </p>

            </div>

        </section>


        <!-- FAQ -->
        <section class="mt-16">

            <h2 class="text-2xl font-bold">
                Questions fréquentes
            </h2>

            <div class="mt-6 space-y-4">

                <details class="p-5 bg-white border border-gray-200 group rounded-2xl">
                    <summary class="font-semibold list-none cursor-pointer">
                        Comment créer un compte ?
                    </summary>

                    <p class="mt-3 text-sm leading-6 text-gray-600">
                        Téléchargez Pop The Ballon depuis l'App Store ou
                        Google Play, puis suivez les étapes d'inscription
                        directement dans l'application.
                    </p>
                </details>


                <details class="p-5 bg-white border border-gray-200 group rounded-2xl">
                    <summary class="font-semibold list-none cursor-pointer">
                        Comment modifier mon profil ?
                    </summary>

                    <p class="mt-3 text-sm leading-6 text-gray-600">
                        Ouvrez votre profil dans l'application puis utilisez
                        les options de modification disponibles pour mettre
                        à jour vos informations.
                    </p>
                </details>


                <details class="p-5 bg-white border border-gray-200 group rounded-2xl">
                    <summary class="font-semibold list-none cursor-pointer">
                        Je n'arrive pas à me connecter
                    </summary>

                    <p class="mt-3 text-sm leading-6 text-gray-600">
                        Vérifiez votre connexion Internet et vos identifiants.
                        Si le problème persiste, contactez notre équipe
                        d'assistance.
                    </p>
                </details>


                <details class="p-5 bg-white border border-gray-200 group rounded-2xl">
                    <summary class="font-semibold list-none cursor-pointer">
                        Comment supprimer mon compte ?
                    </summary>

                    <p class="mt-3 text-sm leading-6 text-gray-600">
                        Vous pouvez demander la suppression de votre compte
                        depuis l'application ou contacter notre équipe
                        d'assistance afin que nous vous accompagnions dans
                        cette démarche.
                    </p>
                </details>

            </div>

        </section>


        <!-- Contact -->
        <section class="px-6 py-10 mt-16 text-center bg-gray-900 rounded-3xl sm:px-10">

            <h2 class="text-2xl font-bold text-white">
                Vous avez encore besoin d'aide ?
            </h2>

            <p class="max-w-xl mx-auto mt-3 text-gray-400">
                Notre équipe est disponible pour vous aider avec votre compte,
                vos conversations, vos paiements ou tout autre problème.
            </p>

            <a href="mailto:support@poptheballon-drc.com"
                class="inline-flex items-center justify-center px-6 py-3 font-semibold text-white transition mt-7 rounded-xl bg-primary hover:opacity-90">
                Contacter le support
            </a>

        </section>

    </main>


    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200">

        <div
            class="flex flex-col max-w-5xl gap-3 px-6 py-8 mx-auto text-sm text-center text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:text-left">

            <p>
                © {{ date('Y') }} Pop The Ballon. Tous droits réservés.
            </p>

            <div class="flex justify-center gap-5">

                <a href="/privacy" class="transition hover:text-primary">
                    Politique de confidentialité
                </a>

                <a href="/terms" class="transition hover:text-primary">
                    Conditions d'utilisation
                </a>

            </div>

        </div>

    </footer>

</body>

</html>
