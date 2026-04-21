<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mulakat AI - Register</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100">
    <main class="mx-auto flex min-h-screen w-full max-w-md items-center px-4 py-12">
        <section class="w-full rounded-3xl border border-zinc-800 bg-zinc-900/70 p-7 shadow-xl shadow-black/30">
            <h1 class="text-2xl font-semibold text-white">Create account</h1>
            <p class="mt-1 text-sm text-zinc-400">Start and track your interviews.</p>

            <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-zinc-200">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-sm text-zinc-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                    @error('name')
                        <div class="mt-2 text-sm text-rose-400">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-zinc-200">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-sm text-zinc-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                    @error('email')
                        <div class="mt-2 text-sm text-rose-400">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-zinc-200">Password</label>
                    <input id="password" name="password" type="password" required class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-sm text-zinc-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                    @error('password')
                        <div class="mt-2 text-sm text-rose-400">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-medium text-zinc-200">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-sm text-zinc-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                </div>

                <button type="submit" class="w-full rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-200">Create account</button>
            </form>

            <p class="mt-5 text-sm text-zinc-400">Already have an account? <a href="{{ route('login') }}" class="font-medium text-indigo-300 hover:text-indigo-200">Login</a></p>
        </section>
    </main>
</body>
</html>
