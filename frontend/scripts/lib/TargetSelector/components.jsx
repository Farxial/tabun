import React from 'react';
import classNames from 'classnames';
import routes from 'lib/routes';

export default class TargetSelectorHeader extends React.Component {
    constructor(props) {
        super(props);

        this.targetTypes = ['topic', 'blog', 'talk'];
        this.state = {
            hidden: true,
            selectedType: props.selectedType
        };
    }
    render() {
        return (
            <div className="dropdown-create">
                 <h2 className="page-header">
                     {this.loc.block_create}
                     <a className="dropdown-create-trigger link-dashed">
                         {this.loc[this.state.selectedType]}
                     </a>
                 </h2>

                 <ul className={classNames("dropdown-menu-create", {"h-hidden": this.state.hidden})}>
                     {
                         this.targetTypes.map((targetType) => {
                            return (
                                <li className={classNames({"active": this.state.selectedType == targetType})}>
                                    <a href={routes[targetType].add}>
                                        {this.loc[targetType]}
                                    </a>
                                </li>
                            );
                         })
                     }
                 </ul>
        </div>
        )
    }
}