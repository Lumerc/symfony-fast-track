import '../assets/styles/app.scss';
import { h, render } from 'preact';
import { Router } from 'preact-router';
import { useState, useEffect } from 'preact/hooks';

import { findConferences } from './api/api';
import Home from './pages/home';
import Conference from './pages/conference';

function App() {
    const [conferences, setConferences] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        console.log('Fetching conferences from:', ENV_API_ENDPOINT + 'api/conferences');
        
        findConferences()
            .then((data) => {
                console.log('Conferences received:', data);
                setConferences(data);
                setLoading(false);
            })
            .catch((err) => {
                console.error('Error fetching conferences:', err);
                setError(err.message);
                setLoading(false);
            });
    }, []);

    if (loading) {
        return <div className="text-center pt-5">Loading conferences...</div>;
    }

    if (error) {
        return (
            <div className="text-center pt-5">
                <h4>Error loading conferences</h4>
                <p>{error}</p>
            </div>
        );
    }

    if (!conferences) {
        return <div className="text-center pt-5">No conferences available</div>;
    }

    return (
        <div>
            <header className="header">
                <nav className="navbar navbar-light bg-light">
                    <div className="container">
                        <a className="navbar-brand mr-4 pr-2" href="/">
                            &#128217; Guestbook
                        </a>
                    </div>
                </nav>

                <nav className="bg-light border-bottom text-center">
                    {conferences.map((conference) => (
                        <a 
                            key={conference.id} 
                            className="nav-conference" 
                            href={`/conference/${conference.slug}`}
                        >
                            {conference.city} {conference.year}
                        </a>
                    ))}
                </nav>
            </header>

            <Router>
                {/* Ключевое исправление: передаем conferences в Home */}
                <Home path="/" conferences={conferences} />
                <Conference path="/conference/:slug" conferences={conferences} />
            </Router>
        </div>
    );
}

render(<App />, document.getElementById('app'));