<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Authorise Perfect10 | Elsewhere</title>

    <style>
        body {
            font-family: system-ui, sans-serif;
            background: #f5f5f5;
            color: #222;
            margin: 0;
            padding: 40px 20px;
        }

        main {
            max-width: 480px;
            margin: 60px auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 32px;
        }

        h1 {
            margin-top: 0;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        button {
            cursor: pointer;
            padding: 12px 18px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background: white;
        }

        button.approve {
            color: white;
            background: #222;
            border-color: #222;
        }
    </style>
</head>
<body>
    <main>
        <h1>Connect to Elsewhere</h1>

        <p>
            <strong>{{ $client->name }}</strong>
            is requesting permission to access your Elsewhere account.
        </p>

        <p>
            This application will be able to read your
            Elsewhere account identity.
        </p>

        <div class="actions">
            <form
                method="POST"
                action="{{ route('passport.authorizations.approve') }}"
            >
                @csrf

                <input
                    type="hidden"
                    name="state"
                    value="{{ $request->state }}"
                >

                <input
                    type="hidden"
                    name="client_id"
                    value="{{ $client->id }}"
                >

                <input
                    type="hidden"
                    name="auth_token"
                    value="{{ $authToken }}"
                >

                <button type="submit" class="approve">
                    Authorise
                </button>
            </form>

            <form
                method="POST"
                action="{{ route('passport.authorizations.deny') }}"
            >
                @csrf
                @method('DELETE')

                <input
                    type="hidden"
                    name="state"
                    value="{{ $request->state }}"
                >

                <input
                    type="hidden"
                    name="client_id"
                    value="{{ $client->id }}"
                >

                <input
                    type="hidden"
                    name="auth_token"
                    value="{{ $authToken }}"
                >

                <button type="submit">
                    Cancel
                </button>
            </form>
        </div>
    </main>
</body>
</html>
