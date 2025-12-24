import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            {...props}
            src="/img/we-logo.png"
            alt="App Logo"
            className={`object-contain ${props.className || 'h-10 w-auto'}`}
        />
    );
}
