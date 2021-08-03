const path = require('path')
const paths = require('../paths')
const fs = require('fs')
const { generateHashCode } = require('./helpers')

const messageToTest = "This file in autogenerated due to the component's override in injection.js."
const autoGeneratedFileMessage = messageToTest + "\n    It should only be created in production builds "
    + "and not commited to your repository after a local build."

function removeCommentedCode (code) {
    // NOTE: very edge case bug, removes previous character in inline comments. Only a problem if a customer writes
    // ComponentInjector.set('componentName',/*{whatever}*/ Comonent) as comma will be removed.
    return code.replace(/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/gm, "")
}

function getCustomerOverrides () {
    var data = fs.readFileSync(path.join(paths.appSrc, 'js/injection.js'), 'utf8')
    if (data) {
        data = removeCommentedCode(data)
        // match "ComponentInjector.set({string}{any nummber of spaces},", pick out string, remove quotes
        return [...data.matchAll(/(ComponentInjector.set\(('|`|")[A-Za-z0-9]+('|`|")\s*,)/g)]
            .map(res => res[0].match(/'[A-Za-z0-9]+'/)[0])
            .map(res => res.substring(1, res.length - 1))
    }
    return []
}

/**
 * @typedef {Object} InjectableComponent
 * @property {string} componentString - The string representing the injection component, the first argument of the ComponentInjector.set/return func
 * @property {string} location - The absolute path of the component file
 * @property {string} originalContents - the orignal contents of the file
 */

/**
 * @param {string} filePath - The absolute path to the file to search for exported ComponentInjector returns
 */
function getInjectorReturnExports (filePath) {
    var data = fs.readFileSync(filePath, 'utf8')
    if (data) {
        originalContents = data
        data = removeCommentedCode(data)
        // match all exports of "ComponentInjector.return({string}," with any negligable spaces, pick out string
        // then map to object array with sting contexts and filepath
        return [...data.matchAll(/(export \s*(default)?\s*ComponentInjector.return\(('|`|")[A-Za-z0-9]+('|`|")\s*,)/g)]
            .map(res => res[0].match(/'[A-Za-z0-9]+'/)[0])
            .map(res => ({
                componentString: res.substring(1, res.length - 1),
                location: filePath,
                originalContents
            })
            )
    }
    return []
}

/**
 * @param {string} dirPath - The absolute path to the parent directory to start searching
 * @param {Array<InjectableComponent>} replacableComponents - The replacable components passed recursively to return on top level
 */
function getInjectionReplacableComponents (dirPath, replacableComponents) {
    replacableComponents = replacableComponents || []
    let currentReplacableComponents = []
    fs.readdirSync(dirPath).forEach(currentPath => {
        currentPath = path.join(dirPath, currentPath)
        var stats = fs.lstatSync(currentPath)
        if (stats.isDirectory()) {
            currentReplacableComponents = getInjectionReplacableComponents(currentPath, currentReplacableComponents)
        }
        else {
            if (stats.isFile()) {
                var splitPath = currentPath.split('.')
                if (splitPath.length > 1) {
                    var ext = splitPath[splitPath.length - 1]
                    if (ext === "tsx" || ext === "jsx" || ext === "ts" || ext === "js") {
                        currentReplacableComponents = currentReplacableComponents.concat(getInjectorReturnExports(currentPath))
                    }
                }
            }
        }
    })
    return currentReplacableComponents.concat(replacableComponents)
}

/**
 * @param {string} replacedComponentString - The string representing the component overriden in their injection.js file
 * @param {string} injectorAlias - The webpack alias string pointing to Catwalk's src/app/injector.js class
 */
function generateReplacedComponentFileContents (replacedComponentString, injectorAlias) {
    // be careful of string quote in insertion if modifying message below
    const alert = `${replacedComponentString} removed from bundle`

    return `/*
    ${autoGeneratedFileMessage}
*/
import ComponentInjector from '${injectorAlias}'
export default ComponentInjector.return('${replacedComponentString}', () => { console.warn('${alert}') })
`
}

/**
 * @param {string} replacedComponentString - The string representing the component overriden in their injection.js file
 * @param {string} injectorAlias - The webpack alias string pointing to Catwalk's src/app/injector.js class
 * @param {string} overwritePathName - The absolute path to the component to be overwritten
 */
function overwriteComponent (replacedComponentString, injectorAlias, overwritePathName) {
    const fileContents = generateReplacedComponentFileContents(replacedComponentString, injectorAlias)
    fs.writeFileSync(overwritePathName, fileContents)
}

/**
 * @param {string} fileContents - The data contents of the component to cache
 * @param {string} cacheFileFolder - The folder of the cached components
 * @param {string} cacheFilename - The filename of the cached component
 */
function cacheReplacableComponent (fileContents, cacheFileFolder, cacheFilename) {
    if (!fs.existsSync(cacheFileFolder)) {
        fs.mkdirSync(cacheFileFolder, { recursive: true })
    }
    fs.writeFileSync(path.join(cacheFileFolder, cacheFilename), fileContents)
}

/**
 * @param {string} replacableComponentLocation - The location of the replacable component
 * @param {string} cacheFileLocation - The location of the original cached component
 */
function restoreReplacedComponent (replacableComponentLocation, cacheFileLocation) {
    var data
    try {
        data = fs.readFileSync(cacheFileLocation, 'utf8')
    }
    catch (err) {
        throw new Error("Cached component previously replaced by component injector has been removed.")
    }
    fs.writeFileSync(replacableComponentLocation, data)
}

/**
 *
 * @param {Array<InjectableComponent>} injectionReplacableComponents - All components available to be overriden by the customer, their name and file location
 * @param {Array<InjectableComponent>} componentsToExclude - All components the customer has set to be overriden in their injection.js file
 * @param {string} injectorAlias - The webpack alias string pointing to Catwalk's src/app/injector.js class
 */
function handleComponentOverwrites (injectionReplacableComponents, componentsToExclude, injectorAlias) {
    injectionReplacableComponents.forEach(injectionReplacableComponent => {
        var exclIndex = componentsToExclude.findIndex(excl => excl.componentString === injectionReplacableComponent.componentString)
        var cacheFileFolder = path.join(paths.catwalk, "src/js/replacedComponents")
        var cacheFilename = generateHashCode(injectionReplacableComponent.location).toString()
        if (exclIndex !== -1) {
            if (injectionReplacableComponent.originalContents.indexOf(messageToTest) === -1) {
                cacheReplacableComponent(injectionReplacableComponent.originalContents, cacheFileFolder, cacheFilename)
                overwriteComponent(componentsToExclude[exclIndex].componentString, injectorAlias, componentsToExclude[exclIndex].location)
                componentsToExclude.splice(exclIndex, 1)
            }
        }
        else {
            if (injectionReplacableComponent.originalContents.indexOf(messageToTest) !== -1) {
                restoreReplacedComponent(injectionReplacableComponent.location, path.join(cacheFileFolder, cacheFilename))
            }
        }
    })
}

/**
 *
 * @param {boolean} PRODUCTION - A boolean indicating whether or not the build is being run in production
 * @param {string} injectorAlias - The webpack alias string pointing to Catwalk's src/app/injector.js class
 */
function overwriteInjectionReplacedComponents (PRODUCTION, componentInjectorAlias) {
    var customerOverrides = getCustomerOverrides()
    if (PRODUCTION) {
        var injectionReplacableComponents = getInjectionReplacableComponents(path.join(paths.catwalk, 'src/js'))
            .concat(getInjectionReplacableComponents(path.join(paths.theme, 'src/js')))

        var componentsToExclude = []

        customerOverrides.forEach(customerOverride => {
            componentsToExclude = componentsToExclude.concat(injectionReplacableComponents
                .filter(val => val.componentString === customerOverride))
        })

        handleComponentOverwrites(injectionReplacableComponents, componentsToExclude, componentInjectorAlias)
    }
    else {
        if (customerOverrides.length > 0) {
            console.info(`Injector component${customerOverrides.length !== 1 && "s"} ${customerOverrides} will be replaced with your injector overrides in production.`)
        }
    }
}

module.exports = overwriteInjectionReplacedComponents
