import 'vite/modulepreload-polyfill'
import {ReactNode, StrictMode} from 'react'
import {createRoot} from 'react-dom/client'

function bootstrap(rootId: string, varName: string, children?: ReactNode) {
    const props: { [key: string]: any } = (window as any)[varName],
        root = document.getElementById(rootId)

    if (root) {
        console.info(varName, props)
        if (children) {
            createRoot(root).render(children)
        }
    } else {
        console.error(`#${rootId} not found!`)
    }
}

declare global {
    const bootstrapVars: {
        foo: string
        rootId: string
    }
}

bootstrap(
    bootstrapVars.rootId,
    'bootstrapVars',
    (
        <StrictMode>
            <div>
                foo={bootstrapVars.foo}
            </div>
        </StrictMode>
    )
)
